<script setup>
import { ref, reactive } from "vue";
import { useLoadingStore } from "@/stores/loading.js";
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import api from '@/axios';
import Dialog from "primevue/dialog";
import IftaLabel from "primevue/iftalabel";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Button from "primevue/button";
import Message from "primevue/message";

const emits = defineEmits(['recarregar-maquinas']);

const dialogVisible = ref(false);

const form = reactive({
    nome: '',
    descricao: ''
});

const loading = useLoadingStore();

const maxCaracteresDescricao = 200;

const toast = useToast();

const resolver = ({ values }) => {
    const errors = {};

    if (!values.nome) {
        errors.nome = [{ message: 'Nome é obrigatório.' }];
    }

    return { values, errors };
}

const adicionarMaquina = async ({ valid }) => {
    if (!valid) {
        return;
    }

    loading.show('Adicionando Máquina...');

    try {
        const resp = await api.post('/maquina', form);

        loading.hide();

        toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Máquina adicionada com sucesso', life: 3000 });

        emits('recarregar-maquinas');

        closeDialog();
    } catch (e) {
        loading.hide();

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível adicionar máquina', life: 3000 });
    }
}

const limpaCampos = () => {
    form.nome = '';
    form.descricao = '';
}

const openDialog = () => {
    limpaCampos();

    dialogVisible.value = true;
}

const closeDialog = () => {
    limpaCampos();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <Dialog header="Adicionar Máquina" v-model:visible="dialogVisible">
        <Form v-slot="$form" :resolver :initial-values="form" class="dialog-content" @submit="adicionarMaquina">
            <div>
                <IftaLabel>
                    <label for="nome">Nome</label>

                    <InputText input-id="nome" name="nome" v-model="form.nome" fluid />
                </IftaLabel>

                <Message
                    v-if="$form.nome?.invalid"
                    severity="error"
                    size="small"
                    variant="simple"
                    style="margin-top: 3px;"
                >
                    {{ $form.nome.error?.message }}
                </Message>
            </div>

            <IftaLabel>
                <label for="descricao">{{ `Descrição (máx. ${maxCaracteresDescricao})` }}</label>

                <Textarea
                    input-id="descricao"
                    name="descricao"
                    auto-resize
                    fluid
                    :maxlength="maxCaracteresDescricao"
                    v-model="form.descricao"
                />
            </IftaLabel>

            <Button label="Salvar" type="submit" icon="pi pi-check" />
        </Form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>
