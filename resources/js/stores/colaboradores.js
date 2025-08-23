import { defineStore } from 'pinia';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";

export const useColaboradoresStore = defineStore('colaboradores', {
    state: () => ({
        data: []
    }),
    actions: {
        async getColaboradores() {
            try {
                const { data } = await api.get('/colaboradores');

                this.data = data.data;
            } catch (e) {
                useToast().add({
                    summary: 'Algo deu errado...',
                    detail: 'Não foi possível carregar colaboradores.',
                    severity: 'error'
                });
            }
        }
    }
});
