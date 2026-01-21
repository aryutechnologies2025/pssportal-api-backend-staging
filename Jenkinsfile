pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_NAME = "pss-api"
        CONTAINER_NAME = "staging_api"
        PORT = "8000"
        BRANCH = "main"
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
                    COMMIT = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                    env.IMAGE_TAG = "${APP_NAME}:${COMMIT}"
                }

                sh """
                docker build -t ${IMAGE_TAG} .
                """
            }
        }

        stage('Run New Container') {
            steps {
                sh """
                docker run -d --name ${CONTAINER_NAME}_new \
                -p ${PORT}:80 \
                ${IMAGE_TAG}
                """
            }
        }

        stage('Health Check') {
            steps {
                sh """
                sleep 10
                curl -f http://localhost:${PORT}/api/health
                """
            }
        }

        stage('Switch Containers') {
            steps {
                sh """
                docker stop ${CONTAINER_NAME} || true
                docker rm ${CONTAINER_NAME} || true
                docker rename ${CONTAINER_NAME}_new ${CONTAINER_NAME}
                """
            }
        }

        stage('Run Migrations & Clear Cache') {
            steps {
                sh """
                docker exec ${CONTAINER_NAME} php artisan migrate --force
                docker exec ${CONTAINER_NAME} php artisan optimize:clear
                """
            }
        }
    }

    post {
        success {
            echo "✅ Deploy Success: ${IMAGE_TAG}"
        }
        failure {
            echo "❌ Deploy Failed — Cleaning up"
            sh """
            docker stop ${CONTAINER_NAME}_new || true
            docker rm ${CONTAINER_NAME}_new || true
            """
        }
    }
}

