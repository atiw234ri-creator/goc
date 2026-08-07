pipeline {

    agent any

    environment {

        APP_NAME = "php-basic-website"

        VERSION = "${BUILD_NUMBER}"

        NEXUS_URL = "http://54.10.10.10:8081"

        REPOSITORY = "php-artifacts"

        SONARQUBE = "SonarQube"

    }

    stages {

        stage('Checkout') {

            steps {

                git branch: 'main',
                credentialsId: 'github-creds',
                url: 'https://github.com/username/php-basic-website.git'

            }
        }

        stage('PHP Syntax Check') {

            steps {

                sh '''
                find . -name "*.php" | xargs -n1 php -l
                '''

            }

        }

        stage('Install Dependencies') {

            steps {

                sh '''
                composer install
                '''

            }

        }

        stage('PHPUnit Test') {

            steps {

                sh '''
                vendor/bin/phpunit
                '''

            }

        }

        stage('Coding Standard') {

            steps {

                sh '''
                vendor/bin/phpcs .
                '''

            }

        }

        stage('SonarQube Analysis') {

            steps {

                withSonarQubeEnv('SonarQube') {

                    sh '''

                    sonar-scanner \
                    -Dsonar.projectKey=php-basic \
                    -Dsonar.sources=. \
                    -Dsonar.php.coverage.reportPaths=coverage.xml

                    '''

                }

            }

        }

        stage('Quality Gate') {

            steps {

                timeout(time: 5, unit: 'MINUTES') {

                    waitForQualityGate abortPipeline: true

                }

            }

        }

        stage('Package') {

            steps {

                sh """

                zip -r ${APP_NAME}-${VERSION}.zip .

                """

            }

        }

        stage('Upload Artifact To Nexus') {

            steps {

                withCredentials([usernamePassword(

                    credentialsId: 'nexus-user-pass',

                    usernameVariable: 'USER',

                    passwordVariable: 'PASS'

                )]) {

                    sh """

                    curl -v -u $USER:$PASS \
                    --upload-file ${APP_NAME}-${VERSION}.zip \
                    ${NEXUS_URL}/repository/${REPOSITORY}/${APP_NAME}/${VERSION}/${APP_NAME}-${VERSION}.zip

                    """

                }

            }

        }

        stage('Download Artifact From Nexus') {

            steps {

                withCredentials([usernamePassword(

                    credentialsId: 'nexus-user-pass',

                    usernameVariable: 'USER',

                    passwordVariable: 'PASS'

                )]) {

                    sh """

                    curl -u $USER:$PASS \
                    -O \
                    ${NEXUS_URL}/repository/${REPOSITORY}/${APP_NAME}/${VERSION}/${APP_NAME}-${VERSION}.zip

                    """

                }

            }

        }

        stage('Deploy To Staging') {

            steps {

                sshagent(['staging-server-ssh']) {

                    sh """

                    scp ${APP_NAME}-${VERSION}.zip ec2-user@STAGING-IP:/tmp

                    ssh ec2-user@STAGING-IP '

                    sudo rm -rf /var/www/html/*

                    sudo unzip -o /tmp/${APP_NAME}-${VERSION}.zip -d /var/www/html

                    sudo chown -R apache:apache /var/www/html

                    sudo systemctl restart httpd

                    '

                    """

                }

            }

        }

        stage('Smoke Test') {

            steps {

                sh '''

                curl http://STAGING-IP

                '''

            }

        }

        stage('Deploy To Production') {

            steps {

                input "Deploy to Production?"

                sshagent(['production-server-ssh']) {

                    sh """

                    scp ${APP_NAME}-${VERSION}.zip ec2-user@PRODUCTION-IP:/tmp

                    ssh ec2-user@PRODUCTION-IP '

                    sudo rm -rf /var/www/html/*

                    sudo unzip -o /tmp/${APP_NAME}-${VERSION}.zip -d /var/www/html

                    sudo chown -R apache:apache /var/www/html

                    sudo systemctl restart httpd

                    '

                    """

                }

            }

        }

    }

    post {

        success {

            echo "Deployment Successful"

        }

        failure {

            echo "Deployment Failed"

        }

    }

}