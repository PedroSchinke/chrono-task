import { defineStore } from 'pinia';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";

export const useMaquinasStore = defineStore('maquinas', {
    state: () => ({
        data: []
    }),
    actions: {
        async getMaquinas() {
            try {
                const { data } = await api.get('/maquinas');

                this.data = data.data;
            } catch (e) {
                useToast().add({
                    summary: 'Algo deu errado...',
                    detail: 'Não foi possível carregar máquinas.',
                    severity: 'error'
                });
            }
        }
    }
});
