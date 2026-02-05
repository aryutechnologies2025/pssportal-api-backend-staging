pipeline {
  agent any

  environment {
    // Runtime-only path (NOT for build)
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

    stage('PROOF — Jenkins Workspace') {
      steps {
        sh '''
          set -e
          echo "📁 Jenkins Workspace:"
          pwd
          ls -la
          echo "📄 Dockerfile (workspace):"
          sed -n '1,15p' Dockerfile
        '''
      }
    }

    stage('Build Image (FROM JENKINS WORKSPACE)') {
      steps {
        dir(env.WORKSPACE) {
          sh '''
            set -e
            echo "🔥 Removing old image if exists..."
            docker rmi -f ${DOCKER_IMAGE}:latest || true

            echo "🐳 Building Docker image from Jenkins workspace..."
            docker build --no-cache -t ${DOCKER_IMAGE}:latest .
          '''
        }
      }
    }

    stage('Deploy Container (USE SERVER .env & uploads)') {
      steps {
        sh '''
          set -e

          echo "🛑 Stopping old container if running..."
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

    stage('PROOF — Files Inside Container (NO DB TOUCH)') {
      steps {
        sh '''
          set -e

          echo "🔎 Checking migration files inside container:"
          docker exec ${CONTAINER_NAME} ls database/migrations | tail -n 5

          echo "🩺 Health Check:"
          sleep 5
          curl -f ${HEALTH_URL}
        '''
      }
    }
  }

  post {
    success {
      echo "✅ DEPLOY SUCCESS — Image built from Jenkins workspace"
    }
    failure {
      echo "❌ DEPLOY FAILED — Check build context or container logs"
    }
  }
}

