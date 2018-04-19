<template>
    <div class="componentLoadingWrapper" dusk="spinner">
        <div class="loader"
             :style="
                'width:'+width +
                '; height:'+height+';' +
                ' border:'+border+';'"
        >
        </div>
    </div>
</template>
<style>
.loader {
  border-style: solid !important;
  border-color: #f3f3f3 !important;
  border-radius: 50%;
  border-top-color: #3498db !important;
  -webkit-animation: spin 1s linear infinite;
  animation: spin 1s linear infinite;
  margin: auto;
}

@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
<script>

    export default{
        mounted(){},
        props:['width','height','border'],
    }
</script>
