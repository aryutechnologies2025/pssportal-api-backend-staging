pipeline {
  agent any

  environment {
    APP_DIR = "/var/www/staging/pssportal-api-backend"
    CONTAINER_NAME = "staging_api"
    DOCKER_IMAGE = "pssportal-api"
    DOCKER_NETWORK = "staging_default"
    HEALTH_URL = "http://127.0.0.1:8001/api/health"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('PROOF — Build Context') {
      steps {
        sh '''
          set -e
          echo "📁 Using backend folder:"
          ls -ld ${APP_DIR}

          echo "📄 Dockerfile preview:"
          sed -n '1,15p' ${APP_DIR}/Dockerfile
        '''
      }
    }

    stage('Build Image (NO CACHE — MATCH MANUAL)') {
      steps {
        sh '''
          set -e
          cd ${APP_DIR}

          echo "🔥 Removing old image..."
          docker rmi -f ${DOCKER_IMAGE}:latest || true

          echo "🐳 Building image..."
          docker build --no-cache -t ${DOCKER_IMAGE}:latest .
        '''
      }
    }

    stage('Deploy Container (MATCH MANUAL)') {
      steps {
        sh '''
          set -e

          echo "🛑 Stopping old container..."
          docker stop ${CONTAINER_NAME} || true
          docker rm ${CONTAINER_NAME} || true

          echo "🚀 Starting new container..."
          docker run -d \
            --name ${CONTAINER_NAME} \
            --network ${DOCKER_NETWORK} \
            --restart unless-stopped \
            --env-file ${APP_DIR}/.env \
            -v /var/www/staging/uploads:/var/www/html/public/uploads \
            -p 8001:80 \
            ${DOCKER_IMAGE}:latest
        '''
      }
    }

    stage('PROOF — Runtime Verification') {
      steps {
        sh '''
          set -e

          echo "🔎 Apache DocumentRoot:"
          docker exec ${CONTAINER_NAME} apachectl -S | grep DocumentRoot

          echo "🩺 Health Check:"
          sleep 5
          curl -f ${HEALTH_URL}
        '''
      }
    }
  }

  post {
    success {
      echo "✅ DEPLOY SUCCESS — MATCHES MANUAL DEPLOY EXACTLY"
    }
    failure {
      echo "❌ DEPLOY FAILED — IMAGE OR CONTAINER STATE INVALID"
    }
  }
}

