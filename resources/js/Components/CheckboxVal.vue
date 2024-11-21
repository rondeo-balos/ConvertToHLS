<script setup>
import { nextTick, ref } from 'vue';
const emit = defineEmits([
    'checked'
]);
const model = defineModel();
const props = defineProps({
    value: {
        type: String,
        required: true
    },
    checked: {
        type: Boolean
    }
});

const checkbox = ref(null);

const checkboxChanged = (event) => {
    nextTick(() => {

        props.checked = checkbox.value.checked;

        if( checkbox.value.checked ) {
            model.value.push( props.value );
            emit('checked');
        } else {
            var index = model.value.indexOf(props.value);
            if (index !== -1) {
                model.value.splice(index, 1);
            }
        }

        console.log(checkbox.value.checked);
    });
}
</script>

<template>
    <input
        type="checkbox" :value="value" @change="checkboxChanged" ref="checkbox"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"/>
</template>
