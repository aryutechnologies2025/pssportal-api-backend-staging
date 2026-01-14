pipeline {
    agent any

    environment {
        APP_NAME = "staging_api"
        IMAGE_NAME = "staging_portal-api"
        PORT = "8001"
        WORKDIR = "/var/www/staging/pssportal-api-backend"
    }

    stages {

        stage('Checkout') {
            steps {
                git branch: 'main',
                    credentialsId: 'github-creds',
                    url: 'https://github.com/<org>/pssportal-api-backend.git'
            }
        }

        stage('Build Image') {
            steps {
                sh """
                cd ${WORKDIR}
                docker build -t ${IMAGE_NAME}:new .
                """
            }
        }

        stage('Deploy') {
            steps {
                sh """
                docker rename ${APP_NAME} ${APP_NAME}_old || true

                docker run -d --name ${APP_NAME} \
                  -p ${PORT}:80 \
                  --env-file .env \
                  --restart unless-stopped \
                  ${IMAGE_NAME}:new
                """
            }
        }

        stage('Migrate & Health Check') {
            steps {
                sh """
                sleep 10
                docker exec ${APP_NAME} php artisan migrate --force
                curl -f http://127.0.0.1:${PORT} || exit 1
                """
            }
        }
    }

    post {
        success {
            sh "docker rm -f ${APP_NAME}_old || true"
        }

        failure {
            sh """
            docker rm -f ${APP_NAME} || true
            docker rename ${APP_NAME}_old ${APP_NAME} || true
            """
        }
    }
}

