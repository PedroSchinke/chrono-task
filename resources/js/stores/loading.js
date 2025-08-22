import { defineStore } from 'pinia'

export const useLoadingStore = defineStore('loading', {
    state: () => ({
        ativo: false,
        message: 'Carregando...',
    }),
    actions: {
        show(message = 'Carregando...') {
            this.ativo = true
            this.message = message
        },
        hide() {
            this.ativo = false
            this.message = 'Carregando...'
        },
    },
})
