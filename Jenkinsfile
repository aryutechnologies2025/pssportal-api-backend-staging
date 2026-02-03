pipeline {
  agent any

  environment {
    SERVER_PATH = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT = "8001"
    DEPLOY_BRANCH = "main"
    DOCKER_IMAGE = "pssportal-api"
    DOCKER_NETWORK = "staging_default"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('Checkout (MAIN)') {
      steps {
        sh '''
          set -e
          sudo chown -R jenkins:jenkins ${SERVER_PATH}
          cd ${SERVER_PATH}

          git fetch origin
          git reset --hard origin/${DEPLOY_BRANCH}

          echo "DEPLOYING COMMIT:"
          git log --oneline -1
        '''
      }
    }

    stage('Build Image') {
      steps {
        sh '''
          set -e
          cd ${SERVER_PATH}

          COMMIT=$(git rev-parse --short HEAD)
          echo "Building image: ${DOCKER_IMAGE}:staging-$COMMIT"

          docker build -t ${DOCKER_IMAGE}:staging-$COMMIT .
          docker tag ${DOCKER_IMAGE}:staging-$COMMIT ${DOCKER_IMAGE}:staging
        '''
      }
    }

    stage('Deploy Container') {
      steps {
        sh '''
          set -e

          docker stop ${LIVE_CONTAINER} || true
          docker rm ${LIVE_CONTAINER} || true

          docker run -d \
            --name ${LIVE_CONTAINER} \
            --network ${DOCKER_NETWORK} \
            -p 0.0.0.0:${LIVE_PORT}:80 \
            --restart unless-stopped \
            --env-file ${SERVER_PATH}/.env \
            ${DOCKER_IMAGE}:staging
        '''
      }
    }

    stage('Health Check (Real Route)') {
      steps {
        sh '''
          set -e
          sleep 10

          echo "Checking API route..."
          curl -f http://127.0.0.1:${LIVE_PORT}/api/contract-dashboard
        '''
      }
    }
  }

  post {
    success {
      echo "✅ DEPLOY SUCCESS — Docker image rebuilt and container replaced"
    }
    failure {
      echo "❌ DEPLOY FAILED — Check logs, old container may still be running"
    }
  }
}

