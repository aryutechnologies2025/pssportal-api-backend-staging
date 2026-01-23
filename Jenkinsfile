pipeline {
  agent any

  environment {
    SERVER_PATH = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT = "8000"
    GIT_BRANCH = "main"
  }

  stages {

    // 1. Fix repo ownership (HOST)
    stage('Fix Repo Ownership') {
      steps {
        sh '''
        set -e
        echo "Fixing Git ownership for Jenkins..."
        sudo chown -R jenkins:jenkins ${SERVER_PATH}
        '''
      }
    }

    // 2. Update server repo
    stage('Update Server Code') {
      steps {
        sh '''
        set -e
        echo "Updating server repo..."
        cd ${SERVER_PATH}
        git fetch origin
        git reset --hard origin/${GIT_BRANCH}
        git rev-parse HEAD
        '''
      }
    }

    // 3. Fix Laravel permissions (INSIDE CONTAINER)
    stage('Fix Laravel Permissions') {
      steps {
        sh '''
        set -e
        echo "Fixing Laravel permissions inside container..."

        docker exec ${LIVE_CONTAINER} sh -c "
          mkdir -p /var/www/html/storage/logs
          mkdir -p /var/www/html/bootstrap/cache

          chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap
          chmod -R 775 /var/www/html/storage /var/www/html/bootstrap
        "
        '''
      }
    }

    // 4. Clear Laravel cache
    stage('Clear App Cache') {
      steps {
        sh '''
        set -e
        docker exec ${LIVE_CONTAINER} php artisan optimize:clear
        '''
      }
    }

    // 5. Health Check
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
      echo "✅ STAGING BACKEND DEPLOY SUCCESS"
    }
    failure {
      echo "❌ STAGING DEPLOY FAILED — CHECK SERVER LOGS"
    }
  }
}

