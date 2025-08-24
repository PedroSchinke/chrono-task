import { defineStore } from 'pinia';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";

export const useTarefasStore = defineStore('tarefas', {
    state: () => ({
        data: []
    }),
    actions: {
        async getTarefas() {
            try {
                const { data } = await api.get('/tarefas');

                this.data = data;
            } catch (e) {
                useToast().add({
                    summary: 'Algo deu errado...',
                    detail: 'Não foi possível carregar tarefas.',
                    severity: 'error'
                });
            }
        },
        updateTarefa(id, values) {
            const tarefa = this.data.find(tarefa => tarefa.id === id);

            Object.entries(values).forEach(([key, value]) => {
                tarefa[key] = value;
            });
        }
    }
});
