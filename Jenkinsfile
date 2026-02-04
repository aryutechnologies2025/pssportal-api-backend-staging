pipeline {
  agent any

  environment {
    CONTAINER_NAME = "staging_employee"
    DEPLOY_BRANCH = "main"
    HEALTH_URL = "http://127.0.0.1:3002"
    WEB_ROOT = "/usr/local/apache2/htdocs"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('Checkout (LOCKED TO MAIN)') {
      steps {
        checkout([
          $class: 'GitSCM',
          branches: [[name: "*/${DEPLOY_BRANCH}"]],
          userRemoteConfigs: scm.userRemoteConfigs
        ])
        sh 'echo "DEPLOYING COMMIT:" && git log --oneline -1'
      }
    }

    stage('Verify Static Frontend Files') {
      steps {
        sh '''
          set -e
          echo "🔍 Verifying static frontend files in repo root..."

          test -f index.html || (echo "❌ index.html missing" && exit 1)
          test -d assets || (echo "❌ assets/ folder missing" && exit 1)

          echo "✅ Static frontend verified"
        '''
      }
    }

    stage('Deploy (ATOMIC CONTAINER SYNC)') {
      steps {
        sh '''
          set -e
          echo "🚀 Deploying into container: ${CONTAINER_NAME}"

          docker exec ${CONTAINER_NAME} mkdir -p ${WEB_ROOT}_new

          echo "📦 Copying frontend files..."
          docker cp index.html ${CONTAINER_NAME}:${WEB_ROOT}_new/
          docker cp assets ${CONTAINER_NAME}:${WEB_ROOT}_new/
          docker cp pss-favicon.jpeg ${CONTAINER_NAME}:${WEB_ROOT}_new/ || true
          docker cp pss.jpg ${CONTAINER_NAME}:${WEB_ROOT}_new/ || true
          docker cp pssAgenciesLogo.svg ${CONTAINER_NAME}:${WEB_ROOT}_new/ || true
          docker cp vite.svg ${CONTAINER_NAME}:${WEB_ROOT}_new/ || true

          echo "🔁 Atomic switch..."
          docker exec ${CONTAINER_NAME} sh -c "
            rm -rf ${WEB_ROOT}_old || true
            mv ${WEB_ROOT} ${WEB_ROOT}_old || true
            mv ${WEB_ROOT}_new ${WEB_ROOT}
          "
        '''
      }
    }

    stage('Health Check') {
      steps {
        sh '''
          set -e
          sleep 3
          curl -f ${HEALTH_URL}
        '''
      }
    }
  }

  post {
    success {
      echo "✅ STATIC FRONTEND DEPLOYED INTO DOCKER CONTAINER"
    }
    failure {
      echo "❌ DEPLOY FAILED — STATIC FILES OR CONTAINER ISSUE"
    }
  }
}

