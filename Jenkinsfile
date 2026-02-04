pipeline {
  agent any

  environment {
    CONTAINER_NAME = "staging_api"
    DEPLOY_BRANCH = "main"
    DOCKER_IMAGE  = "pssportal-api"
    DOCKER_NETWORK = "staging_default"
    HEALTH_API = "http://127.0.0.1:8001/api/health"

    HOST_UPLOADS = "/var/www/staging/uploads"
    CONTAINER_UPLOADS = "/var/www/html/public/uploads"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    // =========================
    // CHECKOUT
    // =========================
    stage('Checkout (LOCKED TO MAIN)') {
      steps {
        checkout([
          $class: 'GitSCM',
          branches: [[name: "*/${DEPLOY_BRANCH}"]],
          userRemoteConfigs: scm.userRemoteConfigs
        ])
        sh '''
          echo "DEPLOYING COMMIT:"
          git log --oneline -1
        '''
      }
    }

    // =========================
    // PRE-FLIGHT CHECKS
    // =========================
    stage('Preflight (Host Sanity)') {
      steps {
        sh '''
          set -e
          echo "🔍 Checking uploads folder on host..."

          if [ ! -d "${HOST_UPLOADS}" ]; then
            echo "❌ Uploads folder missing: ${HOST_UPLOADS}"
            exit 1
          fi

          echo "✅ Host uploads folder exists"
          ls -ld ${HOST_UPLOADS}
        '''
      }
    }

    // =========================
    // BUILD IMAGE (NO CACHE)
    // =========================
    stage('Build Docker Image (NO CACHE)') {
      steps {
        sh '''
          set -e
          echo "🐳 Building backend Docker image (no cache)..."
          docker build --no-cache -t ${DOCKER_IMAGE}:latest .
        '''
      }
    }

    // =========================
    // DEPLOY CONTAINER
    // =========================
    stage('Deploy (ATOMIC + VERIFIED)') {
      steps {
        sh '''
          set -e
          echo "🚀 Deploying backend container..."

          echo "Stopping old container if exists..."
          docker stop ${CONTAINER_NAME} || true
          docker rm ${CONTAINER_NAME} || true

          echo "Starting new container..."
          docker run -d \
            --restart unless-stopped \
            --name ${CONTAINER_NAME} \
            --network ${DOCKER_NETWORK} \
            --restart unless-stopped \
            --env-file /var/www/staging/pssportal-api-backend/.env \
            -v ${HOST_UPLOADS}:${CONTAINER_UPLOADS} \
            -p 8001:80 \
            ${DOCKER_IMAGE}:latest

          echo "⏳ Waiting for container to boot..."
          sleep 5

          echo "🔎 Verifying Apache DocumentRoot..."
          docker exec ${CONTAINER_NAME} apachectl -S | grep -q "/var/www/html/public" || {
            echo "❌ Apache is NOT serving from /var/www/html/public"
            docker exec ${CONTAINER_NAME} apachectl -S
            exit 1
          }

          echo "🔎 Verifying uploads volume mount..."
          docker exec ${CONTAINER_NAME} test -d ${CONTAINER_UPLOADS} || {
            echo "❌ Uploads folder NOT mounted in container"
            exit 1
          }

          echo "🔐 Fixing upload permissions..."
          docker exec ${CONTAINER_NAME} chown -R www-data:www-data ${CONTAINER_UPLOADS}
          docker exec ${CONTAINER_NAME} find ${CONTAINER_UPLOADS} -type d -exec chmod 755 {} \\;
          docker exec ${CONTAINER_NAME} find ${CONTAINER_UPLOADS} -type f -exec chmod 644 {} \\;

          echo "🧹 Refreshing Laravel config cache..."
          docker exec ${CONTAINER_NAME} php artisan config:clear
          docker exec ${CONTAINER_NAME} php artisan config:cache

          echo "✅ Container deployed and verified"
        '''
      }
    }

    // =========================
    // HEALTH CHECK (REAL)
    // =========================
    stage('Health Check (API + FILESYSTEM)') {
      steps {
        sh '''
          set -e
          echo "🩺 Checking API health..."
          curl -f ${HEALTH_API}

          echo "🩺 Checking static file serving..."
          docker exec ${CONTAINER_NAME} test -r ${CONTAINER_UPLOADS} || {
            echo "❌ Uploads path not readable by container"
            exit 1
          }

          echo "✅ Health check passed (API + Filesystem)"
        '''
      }
    }

    // =========================
    // CLEANUP
    // =========================
    stage('Cleanup (SAFE MODE)') {
      steps {
        sh '''
          echo "🧹 Cleaning stopped containers and unused volumes..."
          docker container prune -f || true
          docker volume prune -f || true
          echo "✅ Cleanup done"
        '''
      }
    }
  }

  post {
    success {
      echo "✅ STAGING BACKEND DEPLOY SUCCESS — VERIFIED BUILD"
    }
    failure {
      echo "❌ STAGING BACKEND DEPLOY FAILED — SYSTEM STATE PRESERVED FOR DEBUG"
    }
  }
}

