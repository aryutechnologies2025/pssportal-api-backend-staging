pipeline {
  agent any

  environment {
    SERVER_PATH = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT = "8000"
    DEPLOY_BRANCH = "main"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('Checkout (LOCKED TO MAIN)') {
      steps {
        sh '''
          set -e
          cd ${SERVER_PATH}
          git fetch origin
          git reset --hard origin/${DEPLOY_BRANCH}
          echo "DEPLOYING COMMIT:"
          git log --oneline -1
        '''
      }
    }

    stage('Fix Permissions') {
      steps {
        sh '''
          set -e
          docker exec ${LIVE_CONTAINER} sh -c "
            mkdir -p /var/www/html/storage/logs
            mkdir -p /var/www/html/bootstrap/cache

            chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap
            chmod -R 775 /var/www/html/storage /var/www/html/bootstrap
          "
        '''
      }
    }

    stage('Clear Cache') {
      steps {
        sh '''
          set -e
          docker exec ${LIVE_CONTAINER} php artisan optimize:clear
        '''
      }
    }

    stage('Health Check') {
      steps {
        sh '''
          set -e
          sleep 5
          curl -f http://localhost:${LIVE_PORT}/api/health
        '''
      }
    }
  }

  post {
    success {
      echo "✅ BACKEND DEPLOYED FROM MAIN"
    }
    failure {
      echo "❌ DEPLOY FAILED — CONTAINER NOT TOUCHED"
    }
  }
}

