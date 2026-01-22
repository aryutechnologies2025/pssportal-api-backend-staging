pipeline {
  agent any

  environment {
    APP_NAME        = "pss-api"
    LIVE_CONTAINER = "staging_api"
    TEST_CONTAINER = "staging_api_test"

    LIVE_PORT = "8000"
    TEST_PORT = "9000"

    ENV_FILE = "/var/www/staging/pssportal-api-backend/.env"
    STORAGE  = "/var/www/staging/pssportal-api-backend/storage"
  }

  stages {

    // ----------------------------
    // 1. Checkout
    // ----------------------------
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    // ----------------------------
    // 2. Ensure Storage Exists
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
    // 3. Build Docker Image
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
    // 4. Run Test Container
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
    // 5. Run DB Migrations
    // ----------------------------
    stage('Run Migrations') {
      steps {
        sh '''
        docker exec ${TEST_CONTAINER} php artisan migrate --force
        '''
      }
    }

    // ----------------------------
    // 6. Health Check
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
    // 7. Save Current Live Version (Rollback Memory)
    // ----------------------------
    stage('Save Current Live Version') {
      steps {
        script {
          def oldImage = sh(
            script: "docker inspect ${LIVE_CONTAINER} --format='{{.Config.Image}}' || true",
            returnStdout: true
          ).trim()

          if (oldImage) {
            env.OLD_IMAGE = oldImage
            echo "Saved old image: ${env.OLD_IMAGE}"
          } else {
            echo "No old live container found"
          }
        }
      }
    }

    // ----------------------------
    // 8. Go Live (Safe, No Port Conflict)
    // ----------------------------
    stage('Go Live') {
      steps {
        sh '''
        echo "Stopping old live container..."
        docker rm -f ${LIVE_CONTAINER} || true

        echo "Starting new live container..."
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

  post {
    success {
      sh 'docker rm -f ${TEST_CONTAINER} || true'
      echo "✅ DEPLOY SUCCESS"
    }

    failure {
      echo "❌ DEPLOY FAILED — Attempting rollback..."

      sh '''
      if [ ! -z "$OLD_IMAGE" ]; then
        echo "Restoring old version: $OLD_IMAGE"
        docker rm -f ${LIVE_CONTAINER} || true

        docker run -d --name ${LIVE_CONTAINER} \
          --add-host=host.docker.internal:host-gateway \
          --add-host=staging_mysql:host-gateway \
          -p ${LIVE_PORT}:80 \
          -v ${ENV_FILE}:/var/www/html/.env \
          -v ${STORAGE}:/var/www/html/storage \
          $OLD_IMAGE
      else
        echo "No old image found — rollback not possible"
      fi
      '''
    }
  }
}

