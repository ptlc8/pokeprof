pipeline {
    agent any

    parameters {
        string(name: 'POKEPROF_ASSETS_PATH', defaultValue: params.POKEPROF_ASSETS_PATH ?: '/srv/pokeprof/assets', description: 'Where image of cards and boosters are stored')
        string(name: 'PORTAL_CONNECT_URL', defaultValue: params.PORTAL_CONNECT_URL ?: null, description: 'Ambi portal connect URL')
        string(name: 'PORTAL_AVATAR_URL', defaultValue: params.PORTAL_AVATAR_URL ?: null, description: 'Ambi portal avatar URL')
        string(name: 'PORTAL_USER_URL', defaultValue: params.PORTAL_USER_URL ?: null, description: 'Ambi portal user URL')
        string(name: 'POKEPROF_WEBHOOK_CARD_CREATE', defaultValue: params.POKEPROF_WEBHOOK_CARD_CREATE ?: null, description: 'Webhook URL for card creation')
        string(name: 'POKEPROF_WEBHOOK_CARD_EDIT', defaultValue: params.POKEPROF_WEBHOOK_CARD_EDIT ?: null, description: 'Webhook URL for card edition')
        string(name: 'POKEPROF_WEBHOOK_ERROR', defaultValue: params.POKEPROF_WEBHOOK_ERROR ?: null, description: 'Webhook URL for errors')
    }

    stages {
        stage('Build') {
            steps {
                sh 'docker compose build'
            }
        }
        stage('Deploy') {
            steps {
                sh 'docker compose up --remove-orphans -d'
            }
        }
    }
}
