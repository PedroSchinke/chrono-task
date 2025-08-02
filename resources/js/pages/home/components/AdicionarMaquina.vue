<script setup>
import { ref } from "vue";
import { useToast } from 'primevue/usetoast';
import api from '@/axios';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import MultiSelect from 'primevue/multiselect';
import Divider from 'primevue/divider';
import DatePicker from 'primevue/datepicker';
import Button from "primevue/button";
import ModalLoading from "@/components/ModalLoading.vue";

dayjs.extend(customParseFormat);

const emits = defineEmits(['recarregar-maquinas']);

const dialogVisible = ref(false);

const nome = ref('');

const diasDaSemanaSelecionados = ref([]);
const diasDaSemana = ref([
    {
        label: 'Segunda',
        dia_semana: 'segunda',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Terça',
        dia_semana: 'terca',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Quarta',
        dia_semana: 'quarta',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Quinta',
        dia_semana: 'quinta',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Sexta',
        dia_semana: 'sexta',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Sábado',
        dia_semana: 'sabado',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    },
    {
        label: 'Domingo',
        dia_semana: 'domingo',
        hora_inicio: dayjs('08:00', 'HH:mm').toDate(),
        hora_fim: dayjs('18:00', 'HH:mm').toDate()
    }
]);

const loading = ref(false);
const loadingMessage = ref('Adicionando Máquina...');

const toast = useToast();

const adicionarMaquina = async () => {
    try {
        validaNome()
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Alerta', detail: e.message, life: 3000 });

        return;
    }

    loading.value = true;

    try {
        const resp = await api.post('/maquina', { nome: nome.value, dias_horarios: diasDaSemanaSelecionados.value });
    } catch (e) {
        loading.value = false;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar máquina', life: 3000 });

        return;
    }

    loading.value = false;

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Máquina adicionada com sucesso', life: 3000 });

    emits('recarregar-maquinas');

    closeDialog();
}

const validaNome = () => {
    if (nome.value.trim() === '') {
        throw new Error('É preciso inserir o nome da máquina');
    }
}

const resetarHorasPadrao = () => {
    diasDaSemana.value.forEach(dia => {
        dia.hora_inicio = dayjs('08:00', 'HH:mm').toDate();
        dia.hora_fim = dayjs('18:00', 'HH:mm').toDate();
    });
}

const openDialog = () => {
    nome.value = '';
    diasDaSemanaSelecionados.value = [];
    resetarHorasPadrao();

    dialogVisible.value = true;
}

const closeDialog = () => {
    nome.value = '';
    diasDaSemanaSelecionados.value = [];
    resetarHorasPadrao();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <Dialog header="Adicionar Máquina" v-model:visible="dialogVisible">
        <form class="dialog-content">
            <InputText placeholder="Nome" v-model="nome"/>

            <MultiSelect
                v-model="diasDaSemanaSelecionados"
                :options="diasDaSemana"
                optionLabel="label"
                placeholder="Disponibilidade"
                class="w-full md:w-80"
            />

            <div class="dias-selecionados-container">
                <div v-for="dia of diasDaSemanaSelecionados" :key="dia.dia_semana" class="dia-selecionado-container">
                    <span style="margin-right: auto;">{{ dia.label }}</span>

                    <Divider layout="vertical" />

                    <div class="hora-inputs-container">
                        <div class="hora-input">
                            <label for="hora-inicio-input">Das</label>

                            <DatePicker
                                v-model="dia.hora_inicio"
                                input-id="hora-inicio-input"
                                timeOnly
                                fluid
                                :step-minute="5"
                                style="width: 70px;"
                            />
                        </div>

                        <div class="hora-input">
                            <label for="hora-fim-input">Até</label>

                            <DatePicker
                                v-model="dia.hora_fim"
                                input-id="hora-fim-input"
                                timeOnly
                                fluid
                                :step-minute="5"
                                style="width: 70px;"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <Button label="Salvar" icon="pi pi-check" @click="adicionarMaquina()" />
        </form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dias-selecionados-container {
    max-width: 310px;
}

.dia-selecionado-container {
    display: flex;
    align-items: center;
}

.hora-inputs-container {
    display: flex;
    gap: 10px;
}

.hora-input {
    display: flex;
    align-items: center;
    gap: 5px;
}
</style>
