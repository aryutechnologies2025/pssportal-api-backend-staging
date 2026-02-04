pipeline {
  agent any

  environment {
    CONTAINER_NAME = "staging_api"
    DEPLOY_BRANCH = "main"
    DOCKER_IMAGE  = "pssportal-api"
    DOCKER_NETWORK = "staging_default"
    HEALTH_URL = "http://127.0.0.1:8001/api/health"
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

    stage('Build Docker Image') {
      steps {
        sh '''
          set -e
          echo "🐳 Building backend Docker image..."
          docker build -t ${DOCKER_IMAGE}:latest .
        '''
      }
    }

    stage('Deploy (ATOMIC CONTAINER REPLACE)') {
      steps {
        sh '''
          set -e
          echo "🚀 Deploying backend container..."

          echo "Stopping old container if exists..."
          docker stop ${CONTAINER_NAME} || true
          docker rm ${CONTAINER_NAME} || true

          echo "Starting new container..."
          docker run -d \
            --name ${CONTAINER_NAME} \
            --network ${DOCKER_NETWORK} \
            -p 8001:80 \
            ${DOCKER_IMAGE}:latest

          echo "✅ New container started"
        '''
      }
    }

    stage('Health Check') {
      steps {
        sh '''
          set -e
          echo "🩺 Running health check..."
          sleep 5
          curl -f ${HEALTH_URL}
          echo "✅ Backend is healthy"
        '''
      }
    }

    stage('Cleanup (SAFE MODE)') {
      steps {
        sh '''
          echo "🧹 Cleaning stopped containers and unused volumes..."
          docker container prune -f || true
          docker volume prune -f || true
          echo "✅ Cleanup done (images preserved)"
        '''
      }
    }
  }

  post {
    success {
      echo "✅ STAGING BACKEND DEPLOY SUCCESS"
    }
    failure {
      echo "❌ STAGING BACKEND DEPLOY FAILED — CHECK CONTAINER LOGS"
    }
  }
}

