pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_NAME = "pss-api"
        CONTAINER_NAME = "staging_api"
        LIVE_PORT = "8000"
        TEST_PORT = "8001"
        BRANCH = "main"
        ENV_FILE = "/var/www/staging/pssportal-api-backend/.env"
    }

    stages {

        stage('Checkout') {
            steps {
                git branch: "${BRANCH}", url: "https://github.com/aryutechnologies2025/pssportal-api-backend-staging.git"
                sh 'git log --oneline -1'
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    def COMMIT = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                    env.IMAGE_TAG = "${APP_NAME}:${COMMIT}"
                }
                sh """
                docker build -t ${IMAGE_TAG} .
                """
            }
        }

        stage('Run New Container (Test Port)') {
            steps {
                sh """
                docker run -d --name ${CONTAINER_NAME}_new \
                --add-host=host.docker.internal:host-gateway \
                --add-host=staging_mysql:host-gateway \
                -p ${TEST_PORT}:80 \
                -v ${ENV_FILE}:/var/www/html/.env \
                ${IMAGE_TAG}
                """
            }
        }

        stage('Health Check (Test Port)') {
            steps {
                sh """
                sleep 10
                curl -f http://localhost:${TEST_PORT}/api/health
                """
            }
        }

        stage('Switch Containers (Go Live)') {
            steps {
                sh """
                echo "Stopping old live container..."
                docker stop ${CONTAINER_NAME} || true
                docker rm ${CONTAINER_NAME} || true

                echo "Stopping test container..."
                docker stop ${CONTAINER_NAME}_new || true
                docker rm ${CONTAINER_NAME}_new || true

                echo "Starting new live container..."
                docker run -d --name ${CONTAINER_NAME} \
                --add-host=host.docker.internal:host-gateway \
                --add-host=staging_mysql:host-gateway \
                -p ${LIVE_PORT}:80 \
                -v ${ENV_FILE}:/var/www/html/.env \
                ${IMAGE_TAG}
                """
            }
        }

        stage('Clear Cache & Run Migrations') {
            steps {
                sh """
                docker exec ${CONTAINER_NAME} php artisan optimize:clear
                docker exec ${CONTAINER_NAME} php artisan migrate --force
                """
            }
        }
    }

    post {
        success {
            echo "✅ DEPLOY SUCCESS"
            echo "Image deployed: ${IMAGE_TAG}"
            echo "Live URL: http://localhost:${LIVE_PORT}"
        }

        failure {
            echo "❌ DEPLOY FAILED — CLEANING UP"
            sh """
            docker stop ${CONTAINER_NAME}_new || true
            docker rm ${CONTAINER_NAME}_new || true
            """
        }
    }
}

