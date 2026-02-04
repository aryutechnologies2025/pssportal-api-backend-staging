pipeline {
  agent any

  environment {
    APP_PATH = "/var/www/staging/pssportal-api-backend"
    CONTAINER = "staging_api"
    PORT = "8001"
    BRANCH = "main"
    IMAGE = "pssportal-api"
    NETWORK = "staging_default"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('Preflight') {
      steps {
        sh '''
          set -e
          test -d ${APP_PATH}
          test -f ${APP_PATH}/.env
          docker info > /dev/null
        '''
      }
    }

    stage('Checkout') {
      steps {
        sh '''
          set -e
          cd ${APP_PATH}
          git fetch origin
          git reset --hard origin/${BRANCH}
          git log --oneline -1
        '''
      }
    }

    stage('Build Image') {
      steps {
        sh '''
          set -e
          cd ${APP_PATH}
          docker build -t ${IMAGE}:staging .
        '''
      }
    }

    stage('Deploy') {
      steps {
        sh '''
          set -e

          docker stop ${CONTAINER} || true
          docker rm ${CONTAINER} || true

          docker run -d \
            --name ${CONTAINER} \
            --network ${NETWORK} \
            -p ${PORT}:80 \
            --restart unless-stopped \
            --env-file ${APP_PATH}/.env \
            ${IMAGE}:staging
        '''
      }
    }

    stage('Health Check') {
      steps {
        sh '''
          set -e

          for i in 1 2 3; do
            STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:${PORT}/ || true)
            echo "Attempt $i → HTTP $STATUS"

            if [ "$STATUS" = "200" ] || [ "$STATUS" = "301" ] || [ "$STATUS" = "302" ]; then
              exit 0
            fi

            sleep 5
          done

          exit 1
        '''
      }
    }

    stage('Cleanup') {
      steps {
        sh '''
          docker container prune -f || true
          docker volume prune -f || true

        '''
      }
    }

  }

  post {
    success {
      echo "✅ Staging deploy success"
    }
    failure {
      echo "❌ Staging deploy failed — check container logs"
    }
  }
}

