pipeline {
  agent any

  environment {
    SERVER_PATH   = "/var/www/staging/pssportal-api-backend"
    LIVE_CONTAINER = "staging_api"
    LIVE_PORT      = "8001"
    DEPLOY_BRANCH = "main"
    DOCKER_IMAGE  = "pssportal-api"
    DOCKER_NETWORK = "staging_default"
    BACKUP_CONTAINER = "staging_api_backup"
  }

  options {
    timestamps()
    disableConcurrentBuilds()
  }

  stages {

    stage('Preflight Validation') {
      steps {
        sh '''
          set -e

          echo "Validating environment..."

          test -d ${SERVER_PATH} || (echo "ERROR: App path missing" && exit 1)
          test -f ${SERVER_PATH}/.env || (echo "ERROR: .env file missing" && exit 1)

          docker info > /dev/null || (echo "ERROR: Docker not running" && exit 1)

          echo "Preflight checks passed"
        '''
      }
    }

    stage('Checkout Code') {
      steps {
        sh '''
          set -e
          cd ${SERVER_PATH}

          git fetch origin
          git reset --hard origin/${DEPLOY_BRANCH}

          echo "Deploying commit:"
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
          export NEW_IMAGE=${DOCKER_IMAGE}:staging-$COMMIT

          echo "Building image: $NEW_IMAGE"

          docker build -t $NEW_IMAGE .
          docker tag $NEW_IMAGE ${DOCKER_IMAGE}:staging
        '''
      }
    }

    stage('Backup Running Container') {
      steps {
        sh '''
          set -e

          if docker ps -q -f name=${LIVE_CONTAINER} > /dev/null; then
            echo "Backing up running container..."
            docker rm -f ${BACKUP_CONTAINER} || true
            docker rename ${LIVE_CONTAINER} ${BACKUP_CONTAINER}
          else
            echo "No running container to backup"
          fi
        '''
      }
    }

    stage('Deploy New Container') {
      steps {
        sh '''
          set -e

          echo "Starting new container..."

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

    stage('Health Check (Backend Reachability)') {
      steps {
        sh '''
          set -e

          echo "Checking backend availability on /"

          for i in 1 2 3 4 5; do
            STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:${LIVE_PORT}/ || true)
            echo "Attempt $i → HTTP $STATUS"

            if [ "$STATUS" = "200" ] || [ "$STATUS" = "301" ] || [ "$STATUS" = "302" ]; then
              echo "Backend is reachable"
              exit 0
            fi

            sleep 5
          done

          echo "Health check failed"
          exit 1
        '''
      }
    }

    stage('Cleanup Old Images') {
      steps {
        sh '''
          echo "Cleaning unused images..."
          docker image prune -af || true
        '''
      }
    }

  }

  post {

    success {
      sh '''
        echo "Deployment successful"

        if docker ps -a -q -f name=${BACKUP_CONTAINER} > /dev/null; then
          docker rm -f ${BACKUP_CONTAINER}
        fi
      '''
    }

    failure {
      sh '''
        echo "Deployment failed — rolling back"

        docker rm -f ${LIVE_CONTAINER} || true

        if docker ps -a -q -f name=${BACKUP_CONTAINER} > /dev/null; then
          docker rename ${BACKUP_CONTAINER} ${LIVE_CONTAINER}
          docker start ${LIVE_CONTAINER}
          echo "Rollback completed"
        else
          echo "No backup container found — manual intervention required"
        fi
      '''
    }
  }
}

