<template>
    <div class="customFieldGroup">
        <div class="groupHeader">
            <h4>{{ group.title }}</h4>
        </div>

        <div class="fields">
            <customField
                    v-for="(field, index) in group.fields"
                    :vModel="group.slug+'__'+field.slug"
                    :groupSlug="group.slug"
                    :field="field"
                    :index="index"
                    :key="index"
                    :lang="lang"
                    :isSubField="false"
                    :trans="trans"
                    :childrenFieldsGroups="childrenFieldsGroups"
                    :customFieldValues="customFieldValues"></customField>
        </div>
    </div>
</template>
<style scoped>
    .customFieldGroup{
        width: 100%;
        margin-bottom:30px;
    }
    .groupHeader{
        width: 100%;
        height: 40px;
        background-color: #EAEAEA;
    }
    .groupHeader h4{
        color: #333;
        font-weight: bold;
        padding-left: 30px;
        padding-top: 12px;
    }
    .fields{
        margin-top: 30px;
    }
</style>
<script>
    export default{
        props:['group','lang','customFieldValues','trans','childrenFieldsGroups'],
        data(){
            return{

            }
        },
    }
</script>
