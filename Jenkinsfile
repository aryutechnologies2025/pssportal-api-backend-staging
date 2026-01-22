pipeline {
  agent any

  environment {
    APP_NAME        = "pss-api"
    LIVE_CONTAINER = "staging_api"
    TEST_CONTAINER = "staging_api_test"

    LIVE_PORT = "8000"
    TEST_PORT = "9000"

    HOST_PATH = "/var/www/staging/pssportal-api-backend"
    ENV_FILE  = "/var/www/staging/pssportal-api-backend/.env"
    STORAGE   = "/var/www/staging/pssportal-api-backend/storage"
    BOOTSTRAP= "/var/www/staging/pssportal-api-backend/bootstrap/cache"

    IMAGE_TAG = ""
  }

  stages {

    // ----------------------------
    // 1. Checkout Code
    // ----------------------------
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    // ----------------------------
    // 2. Prepare Host Storage
    // ----------------------------
    stage('Prepare Host Storage') {
      steps {
        sh '''
        echo "Preparing Laravel runtime directories on HOST..."

        mkdir -p ${STORAGE}/framework/views
        mkdir -p ${STORAGE}/framework/cache
        mkdir -p ${STORAGE}/framework/sessions

        chown -R www-data:www-data ${STORAGE} ${BOOTSTRAP}
        chmod -R 775 ${STORAGE} ${BOOTSTRAP}
        '''
      }
    }

    // ----------------------------
    // 3. Build Docker Image
    // ----------------------------
    stage('Build Image') {
      steps {
        script {
          IMAGE_TAG = sh(
            script: "git rev-parse --short HEAD",
            returnStdout: true
          ).trim()
        }

        sh '''
        echo "Building image ${APP_NAME}:${IMAGE_TAG}"
        docker build -t ${APP_NAME}:${IMAGE_TAG} .
        '''
      }
    }

    // ----------------------------
    // 4. Start Test Container
    // ----------------------------
    stage('Run Test Container') {
      steps {
        sh '''
        docker rm -f ${TEST_CONTAINER} || true

        docker run -d --name ${TEST_CONTAINER} \
          --add-host=host.docker.internal:host-gateway \
          --add-host=staging_mysql:host-gateway \
          -p ${TEST_PORT}:80 \
          -v ${ENV_FILE}:/var/www/html/.env \
          -v ${STORAGE}:/var/www/html/storage \
          ${APP_NAME}:${IMAGE_TAG}
        '''
      }
    }

    // ----------------------------
    // 5. Health Check
    // ----------------------------
    stage('Health Check') {
      steps {
        sh '''
        echo "Waiting for API to boot..."
        sleep 10

        curl -f http://localhost:${TEST_PORT}/api/health
        '''
      }
    }

    // ----------------------------
    // 6. Run DB Migrations
    // ----------------------------
    stage('Run Migrations') {
      steps {
        sh '''
        docker exec ${TEST_CONTAINER} php artisan migrate --force
        '''
      }
    }

    // ----------------------------
    // 7. Go Live (Same Port Swap)
    // ----------------------------
    stage('Go Live') {
      steps {
        sh '''
        echo "Stopping old live container..."
        docker rm -f ${LIVE_CONTAINER} || true

        echo "Starting new live container on port ${LIVE_PORT}..."
        docker run -d --name ${LIVE_CONTAINER} \
          --add-host=host.docker.internal:host-gateway \
          --add-host=staging_mysql:host-gateway \
          -p ${LIVE_PORT}:80 \
          -v ${ENV_FILE}:/var/www/html/.env \
          -v ${STORAGE}:/var/www/html/storage \
          ${APP_NAME}:${IMAGE_TAG}
        '''
      }
    }
  }

  // ----------------------------
  // 8. Post Actions
  // ----------------------------
  post {
    success {
      sh '''
      docker rm -f ${TEST_CONTAINER} || true
      '''
      echo "✅ DEPLOY SUCCESS — Frontend now uses new backend automatically"
    }

    failure {
      echo "❌ DEPLOY FAILED — Live container NOT touched"
      echo "Test container kept for debugging"
    }
  }
}

