pipeline {
  agent any

  environment {
    SERVER_PATH = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT = "8000"
    GIT_BRANCH = "main"
  }

  stages {

    // 1. Pull latest code to server
    stage('Update Server Code') {
      steps {
        sh '''
        set -e
        echo "Updating server repo..."
        cd ${SERVER_PATH}

        git fetch origin
        git reset --hard origin/${GIT_BRANCH}

        echo "Current commit:"
        git rev-parse HEAD
        '''
      }
    }

    // 2. Clear Laravel cache (NO MIGRATION)
    stage('Clear App Cache') {
      steps {
        sh '''
        set -e
        echo "Clearing Laravel cache..."
        docker exec ${LIVE_CONTAINER} php artisan optimize:clear
        '''
      }
    }
    
    // ----------------------------
// Fix Laravel Permissions
// ----------------------------
stage('Fix Permissions') {
  steps {
    sh '''
    set -e
    echo "Fixing Laravel permissions..."

    docker exec staging_api sh -c "
      mkdir -p /var/www/html/storage/logs
      mkdir -p /var/www/html/bootstrap/cache

      chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap
      chmod -R 775 /var/www/html/storage /var/www/html/bootstrap
    "
    '''
  }
}


    // 3. Health Check
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
      echo "✅ STAGING FILE DEPLOY SUCCESS"
    }
    failure {
      echo "❌ STAGING DEPLOY FAILED — CHECK SERVER LOGS"
    }
  }
}

