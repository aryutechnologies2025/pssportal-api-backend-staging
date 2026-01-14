pipeline {
    agent any

    environment {
        APP_NAME = "portal-api"
        COMPOSE_DIR = "/var/www/staging"
        SERVICE_NAME = "portal-api"
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Image') {
            steps {
                sh '''
                  cd ${COMPOSE_DIR}
                  docker compose build ${SERVICE_NAME}
                '''
            }
        }

        stage('Deploy (Safe)') {
            steps {
                sh '''
                  cd ${COMPOSE_DIR}

                  echo "Stopping old container (if running)"
                  docker compose stop ${SERVICE_NAME} || true

                  echo "Starting updated container"
                  docker compose up -d ${SERVICE_NAME}
                '''
            }
        }

        stage('Migrate & Cache') {
            steps {
                sh '''
                  cd ${COMPOSE_DIR}

                  echo "Running migrations"
                  docker compose exec -T ${SERVICE_NAME} php artisan migrate --force

                  echo "Clearing caches"
                  docker compose exec -T ${SERVICE_NAME} php artisan config:clear
                  docker compose exec -T ${SERVICE_NAME} php artisan cache:clear
                  docker compose exec -T ${SERVICE_NAME} php artisan route:clear
                  docker compose exec -T ${SERVICE_NAME} php artisan view:clear
                '''
            }
        }

        stage('Health Check') {
            steps {
                sh '''
                  echo "Checking API health"
                  curl -f https://portalapi-staging.pssagencies.com || exit 1
                '''
            }
        }
    }

    post {
        success {
            echo "✅ STAGING API DEPLOY SUCCESSFUL"
        }

        failure {
            echo "❌ DEPLOY FAILED — Rolling back"

            sh '''
              cd ${COMPOSE_DIR}
              docker compose up -d ${SERVICE_NAME}
            '''
        }
    }
}

