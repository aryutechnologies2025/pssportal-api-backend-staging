pipeline {
  agent any

  environment {
    SERVER_PATH  = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT = "8000"
    GIT_BRANCH = "main"
  }

  stages {

    // ----------------------------
    // 0. Ownership Safety Net
    // ----------------------------
    stage('Pre-Fix Ownership') {
      steps {
        sh '''
        set -e
        echo "Fixing repo ownership for Jenkins..."

        sudo chown -R jenkins:jenkins ${SERVER_PATH}

        sudo chown -R www-data:www-data ${SERVER_PATH}/storage
        sudo chown -R www-data:www-data ${SERVER_PATH}/bootstrap/cache

        sudo chmod -R 775 ${SERVER_PATH}/storage
        sudo chmod -R 775 ${SERVER_PATH}/bootstrap/cache
        '''
      }
    }

    // ----------------------------
    // 1. Pull latest code
    // ----------------------------
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

    // ----------------------------
    // 2. Clear Laravel cache
    // ----------------------------
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
    // 3. Fix Laravel Runtime Permissions
    // ----------------------------
    stage('Fix Permissions') {
      steps {
        sh '''
        set -e
        echo "Fixing Laravel runtime permissions..."

        docker exec ${LIVE_CONTAINER} sh -c "
          mkdir -p /var/www/html/storage/logs
          mkdir -p /var/www/html/bootstrap/cache

          chown -R www-data:www-data /var/www/html/storage
          chown -R www-data:www-data /var/www/html/bootstrap/cache

          chmod -R 775 /var/www/html/storage
          chmod -R 775 /var/www/html/bootstrap/cache
        "
        '''
      }
    }

    // ----------------------------
    // 4. Health Check
    // ----------------------------
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

