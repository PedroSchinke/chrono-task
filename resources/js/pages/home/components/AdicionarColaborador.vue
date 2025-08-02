<script setup>
import { reactive, ref } from "vue";
import { useToast } from 'primevue/usetoast';
import { isValidEmail, normalizeCpf } from "@/helpers/stringHelper.js";
import { Form } from "@primevue/forms";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import InputMask from "primevue/inputmask";
import IftaLabel from "primevue/iftalabel";
import Button from "primevue/button";
import api from "@/axios.js";
import Toast from "primevue/toast";
import ModalLoading from "@/components/ModalLoading.vue";

const emits = defineEmits(['recarregar-colaboradores']);

const dialogVisible = ref(false);

const loading = ref(false);
const loadingMessage = ref('Adicionando Colaborador...');

const form = reactive({
    primeiro_nome: '',
    sobrenome: '',
    cpf: '',
    email: ''
});

const resolver = ({ values }) => {
    const errors = {};

    if (!values.primeiro_nome) {
        errors.primeiro_nome = [{ message: 'Primeiro nome é obrigatório.' }];
    }

    if (!values.sobrenome) {
        errors.sobrenome = [{ message: 'Sobrenome é obrigatório.' }];
    }

    if (!values.cpf) {
        errors.cpf = [{ message: 'CPF é obrigatório.' }];
    } else {
        const cpfDigits = normalizeCpf(values.cpf).toString().length;

        if (cpfDigits !== 11) {
            errors.cpf = [{message: 'Insira um CPF válido.'}];
        }
    }

    if (!values.email) {
        errors.email = [{ message: 'Email é obrigatório.' }];
    } else if (!isValidEmail(values.email)) {
        errors.email = [{ message: 'Insira um email válido.' }];
    }

    return { values, errors };
}

const toast = useToast();

const adicionarColaborador = async ({ valid, values }) => {
    if (!valid) {
        toast.add({ summary: 'Atenção', detail: 'Preencha todos os campos.', severity: 'error', life: 3000 });

        return;
    }

    loading.value = true;

    const params = {
        primeiro_nome: values.primeiro_nome,
        sobrenome: values.sobrenome,
        cpf: normalizeCpf(values.cpf.replace(/\D/g, '')),
        email: values.email.toLowerCase()
    }

    try {
        await api.post('/colaboradores', params);
    } catch (e) {
        loading.value = false;

        toast.add({
            severity: 'error',
            summary: 'Algo deu errado...',
            detail: 'Não foi possível adicionar colaborador.',
            life: 4000
        });

        return;
    }

    loading.value = false;

    toast.add({ severity: 'success', summary: 'Sucesso!', detail: 'Colaborador adicionado com sucesso', life: 3000 });

    emits('recarregar-colaboradores');

    closeDialog();
}

const resetForm = () => {
    form.primeiro_nome = '';
    form.sobrenome = '';
    form.cpf = '';
    form.email = '';
}

const openDialog = () => {
    resetForm();

    dialogVisible.value = true;
}

const closeDialog = () => {
    resetForm();

    dialogVisible.value = false;
}

defineExpose({ openDialog, closeDialog });
</script>

<template>
    <Toast />

    <ModalLoading :is-loading="loading" :message="loadingMessage" />

    <Dialog header="Adicionar Colaborador" v-model:visible="dialogVisible">
        <Form v-slot="$form" :initial-values="form" :resolver class="dialog-content" @submit="adicionarColaborador">
            <IftaLabel>
                <label for="primeiro-nome">Primeiro Nome</label>
                <InputText id="primeiro-nome" name="primeiro_nome" v-model="form.primeiro_nome" fluid />

                <Message v-if="$form.primeiro_nome?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.primeiro_nome.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="sobrenome">Sobrenome</label>
                <InputText id="sobrenome" name="sobrenome" v-model="form.sobrenome" fluid />

                <Message v-if="$form.sobrenome?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.sobrenome.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="cpf">CPF</label>
                <InputMask id="cpf" name="cpf" mask="999.999.999-99" v-model="form.cpf" fluid />

                <Message v-if="$form.cpf?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.cpf.error?.message }}
                </Message>
            </IftaLabel>

            <IftaLabel>
                <label for="email">Email</label>
                <InputText id="email" name="email" type="text" v-model="form.email" fluid />

                <Message v-if="$form.email?.invalid" severity="error" size="small" variant="simple" class="input-message">
                    {{ $form.email.error?.message }}
                </Message>
            </IftaLabel>

            <Button label="Salvar" icon="pi pi-check" type="submit" />
        </Form>
    </Dialog>
</template>

<style scoped>
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.input-message {
    margin-top: 3px;
}
</style>
