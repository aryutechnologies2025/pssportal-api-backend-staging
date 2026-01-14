pipeline {
    agent any

    environment {
        APP_NAME   = "staging_api"
        IMAGE_NAME = "staging_portal-api"
        APP_PATH   = "/var/www/staging/pssportal-api-backend"
        ENV_FILE   = "/var/www/staging/pssportal-api-backend/.env"
        PORT       = "8001"
    }

    stages {

        stage('Build Image') {
            steps {
                sh """
                cd ${APP_PATH}
                docker build -t ${IMAGE_NAME}:latest .
                """
            }
        }

        stage('Deploy (Safe)') {
            steps {
                sh """
                echo "Stopping old container ONLY after successful build"

                if docker ps | grep -q ${APP_NAME}; then
                    docker stop ${APP_NAME}
                    docker rm ${APP_NAME}
                fi

                docker run -d \\
                  --name ${APP_NAME} \\
                  -p ${PORT}:80 \\
                  --env-file ${ENV_FILE} \\
                  --restart unless-stopped \\
                  ${IMAGE_NAME}:latest
                """
            }
        }

        stage('Health Check') {
            steps {
                sh """
                sleep 10
                curl -f http://127.0.0.1:${PORT}
                """
            }
        }
    }

    post {
        failure {
            echo "❌ Build failed — old container was NOT touched"
        }
    }
}

