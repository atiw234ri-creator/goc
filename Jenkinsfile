
pipeline {

    agent {
        label 'agentRunner'
    }

    environment {

        APP_NAME = "php-basic-website"
        VERSION = "${BUILD_NUMBER}"

        NEXUS_URL = "http://54.10.10.10:8081"
        REPOSITORY = "php-artifacts"

        SONARQUBE = "SonarQube"
        SONAR_SCANNER = tool 'SonarScanner'
    }

    stages {

        stage('Checkout') {
            steps {

                git(
                    branch: 'main',
                    credentialsId: 'github-creds',
                    url: 'https://github.com/atiw234ri-creator/goc.git'
                )
            }
        }


        stage('Check Environment') {
            steps {

                sh '''
                    echo "======================================"
                    echo "Checking Agent Environment"
                    echo "======================================"

                    echo "Git:"
                    git --version

                    echo "PHP:"
                    php --version

                    echo "Composer:"
                    composer --version

                    echo "Curl:"
                    curl --version | head -1

                    echo "Zip:"
                    zip -v | head -2

                    echo "======================================"
                '''
            }
        }


        stage('PHP Syntax Check') {
            steps {

                sh '''
                    echo "Checking PHP syntax..."

                    find . \
                    -type f \
                    -name "*.php" \
                    -not -path "./vendor/*" \
                    -print0 | xargs -0 -n1 php -l
                '''
            }
        }


        stage('Install Dependencies') {
            steps {

                sh '''
                    echo "Installing Composer dependencies..."

                    composer install \
                        --no-interaction \
                        --prefer-dist
                '''
            }
        }


        stage('PHPUnit Test') {
            steps {

                sh '''
                    echo "Running PHPUnit tests..."

                    vendor/bin/phpunit
                '''
            }
        }


        stage('Coding Standard') {
            steps {

                sh '''
                    echo "Running PHP CodeSniffer..."

                    vendor/bin/phpcs . \
                        --ignore=vendor/*
                '''
            }
        }


        stage('SonarQube Analysis') {
            steps {

                withSonarQubeEnv('SonarQube') {

                    sh '''
                        echo "Running SonarQube analysis..."

                        ${SONAR_SCANNER}/bin/sonar-scanner \
                        -Dsonar.projectKey=php-basic \
                        -Dsonar.projectName=php-basic \
                        -Dsonar.sources=. \
                        -Dsonar.exclusions=vendor/**,tests/**
                    '''
                }
            }
        }


        stage('Quality Gate') {
            steps {

                timeout(
                    time: 5,
                    unit: 'MINUTES'
                ) {

                    waitForQualityGate(
                        abortPipeline: true
                    )
                }
            }
        }


        stage('Package') {
            steps {

                sh '''
                    echo "Creating application package..."

                    zip -r "${APP_NAME}-${VERSION}.zip" . \
                        -x ".git/*" \
                        -x "Jenkinsfile" \
                        -x "*.zip"
                '''
            }
        }


        stage('Upload Artifact To Nexus') {
            steps {

                withCredentials([
                    usernamePassword(
                        credentialsId: 'nexus-user-pass',
                        usernameVariable: 'NEXUS_USER',
                        passwordVariable: 'NEXUS_PASSWORD'
                    )
                ]) {

                    sh '''
                        echo "Uploading artifact to Nexus..."

                        curl -f -v \
                        -u "${NEXUS_USER}:${NEXUS_PASSWORD}" \
                        --upload-file "${APP_NAME}-${VERSION}.zip" \
                        "${NEXUS_URL}/repository/${REPOSITORY}/${APP_NAME}/${VERSION}/${APP_NAME}-${VERSION}.zip"
                    '''
                }
            }
        }


        stage('Download Artifact From Nexus') {
            steps {

                withCredentials([
                    usernamePassword(
                        credentialsId: 'nexus-user-pass',
                        usernameVariable: 'NEXUS_USER',
                        passwordVariable: 'NEXUS_PASSWORD'
                    )
                ]) {

                    sh '''
                        echo "Downloading artifact from Nexus..."

                        curl -f \
                        -u "${NEXUS_USER}:${NEXUS_PASSWORD}" \
                        -o "${APP_NAME}-${VERSION}-from-nexus.zip" \
                        "${NEXUS_URL}/repository/${REPOSITORY}/${APP_NAME}/${VERSION}/${APP_NAME}-${VERSION}.zip"
                    '''
                }
            }
        }


        stage('Deploy To Staging') {
            steps {

                sshagent(['staging-server-ssh']) {

                    sh '''
                        echo "Deploying to staging..."

                        scp \
                        "${APP_NAME}-${VERSION}-from-nexus.zip" \
                        ec2-user@STAGING-IP:/tmp/

                        ssh ec2-user@STAGING-IP "
                            sudo rm -rf /var/www/html/*
                            sudo unzip -o /tmp/${APP_NAME}-${VERSION}-from-nexus.zip -d /var/www/html
                            sudo chown -R apache:apache /var/www/html
                            sudo systemctl restart httpd
                        "
                    '''
                }
            }
        }


        stage('Smoke Test') {
            steps {

                sh '''
                    echo "Running staging smoke test..."

                    curl -f http://STAGING-IP
                '''
            }
        }


        stage('Deploy To Production') {
            steps {

                input(
                    message: 'Deploy to Production?',
                    ok: 'Deploy'
                )

                sshagent(['production-server-ssh']) {

                    sh '''
                        echo "Deploying to production..."

                        scp \
                        "${APP_NAME}-${VERSION}-from-nexus.zip" \
                        ec2-user@PRODUCTION-IP:/tmp/

                        ssh ec2-user@PRODUCTION-IP "
                            sudo rm -rf /var/www/html/*
                            sudo unzip -o /tmp/${APP_NAME}-${VERSION}-from-nexus.zip -d /var/www/html
                            sudo chown -R apache:apache /var/www/html
                            sudo systemctl restart httpd
                        "
                    '''
                }
            }
        }

    }


    post {

        success {
            echo "======================================"
            echo "Deployment Successful"
            echo "======================================"
        }

        failure {
            echo "======================================"
            echo "Deployment Failed"
            echo "======================================"
        }

        always {
            echo "Pipeline execution completed."
        }
    }
}
