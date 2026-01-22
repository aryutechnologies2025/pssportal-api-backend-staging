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
  }

  stages {

    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    // ----------------------------
    // 1. Ensure Storage Exists
    // ----------------------------
    stage('Ensure Storage Structure') {
      steps {
        sh '''
        mkdir -p ${STORAGE}/framework/views
        mkdir -p ${STORAGE}/framework/cache
        mkdir -p ${STORAGE}/framework/sessions
        '''
      }
    }

    // ----------------------------
    // 2. Build Docker Image
    // ----------------------------
    stage('Build Image') {
      steps {
        script {
          def tag = sh(
            script: "git rev-parse --short HEAD",
            returnStdout: true
          ).trim()

          if (!tag) {
            error("IMAGE_TAG is empty — git commit hash not found")
          }

          env.IMAGE_TAG = tag
          echo "Using image tag: ${env.IMAGE_TAG}"
        }

        sh "docker build -t ${APP_NAME}:${IMAGE_TAG} ."
      }
    }

    // ----------------------------
    // 3. Run Test Container
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
    // 4. Run DB Migrations (BEFORE health)
    // ----------------------------
    stage('Run Migrations') {
      steps {
        sh '''
        docker exec ${TEST_CONTAINER} php artisan migrate --force
        '''
      }
    }

    // ----------------------------
    // 5. Health Check
    // ----------------------------
    stage('Health Check') {
      steps {
        sh '''
        sleep 10
        curl -f http://localhost:${TEST_PORT}/api/health
        '''
      }
    }

    // ----------------------------
    // 6. Go Live (Safe Swap)
    // ----------------------------
    stage('Go Live') {
      steps {
        sh '''
        echo "Starting new live container..."

        docker run -d --name ${LIVE_CONTAINER}_new \
          --add-host=host.docker.internal:host-gateway \
          --add-host=staging_mysql:host-gateway \
          -p ${LIVE_PORT}:80 \
          -v ${ENV_FILE}:/var/www/html/.env \
          -v ${STORAGE}:/var/www/html/storage \
          ${APP_NAME}:${IMAGE_TAG}

        echo "Stopping old live container..."
        docker rm -f ${LIVE_CONTAINER} || true

        echo "Renaming new container..."
        docker rename ${LIVE_CONTAINER}_new ${LIVE_CONTAINER}
        '''
      }
    }
  }

  post {
    success {
      sh 'docker rm -f ${TEST_CONTAINER} || true'
      echo "✅ DEPLOY SUCCESS"
    }

    failure {
      echo "❌ DEPLOY FAILED — Live container NOT touched"
      echo "Test container kept for debugging"
    }
  }
}

