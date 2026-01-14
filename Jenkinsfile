pipeline {
    agent any

    environment {
        COMPOSE_DIR = "/var/www/staging"
        SERVICE     = "portal-api"
        CONTAINER   = "staging_api"
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
                  docker-compose build ${SERVICE}
                '''
            }
        }

        stage('Deploy (Safe & Correct)') {
            steps {
                sh '''
                  cd ${COMPOSE_DIR}

                  echo "Stopping running container if exists"
                  docker stop ${CONTAINER} || true

                  echo "Removing container if exists"
                  docker rm ${CONTAINER} || true

                  echo "Starting new container via compose"
                  docker-compose up -d ${SERVICE}
                '''
            }
        }

        stage('Migrate & Cache') {
            steps {
                sh '''
                  cd ${COMPOSE_DIR}

                  docker-compose exec -T ${SERVICE} php artisan migrate --force
                  docker-compose exec -T ${SERVICE} php artisan config:clear
                  docker-compose exec -T ${SERVICE} php artisan cache:clear
                  docker-compose exec -T ${SERVICE} php artisan route:clear
                  docker-compose exec -T ${SERVICE} php artisan view:clear
                '''
            }
        }

        stage('Health Check') {
            steps {
                sh '''
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
            echo "❌ DEPLOY FAILED — API container left untouched if running"
        }
    }
}

