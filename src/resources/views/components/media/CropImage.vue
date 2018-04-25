<template>
    <!-- CROP WRAPPER -->
    <div class="cropImageWrapper">
        <i class="fa fa-times fa-2x" id="closeCropBtn" aria-hidden="true" @click="cancelCrop"></i>
        <div class="cropImageContainer">

            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-11" id="filesMediaPopup">
                <div class="cropTitle">
                    <h1>{{trans.__title}}</h1>
                </div>

                <div class="image-decorator">
                    <img alt="Image principale" id="imageToBeCropped" :src="generateUrl('/'+selected_image.url)"/>
                </div>

            </div>

            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-3" id="thumbPanel">
                <div id="imagePrev">

                </div>
            </div>

            <div id="cropImageBtns">
                <hr>
                <div class="btn-container">
                    <button class="btn btn-warning" @click="cancelCrop">{{trans.__cancelBtn}}</button>
                    <button class="btn btn-primary" @click="cropImage">{{trans.__cropBtn}}</button>
                </div>
            </div>

        </div>

    </div>
    <!-- CROP WRAPPER -->
</template>

<script>
    import { globalComputed } from '../../mixins/globalComputed';
    import { globalMethods } from '../../mixins/globalMethods';
    import { globalData } from '../../mixins/globalData';
    import { globalUpdated } from '../../mixins/globalUpdated';
    export default{
        mixins: [globalComputed, globalMethods, globalData, globalUpdated],
        mounted(){
                var global = this;

                // CROP ******************
                var img = new Image();
                var cropWindowWidth = "";
                var cropWindowHeight = "";
                var x = "";
                var y = "";
                img.src = this.generateUrl('/'+this.selected_image.url);
                var ratio = "";

                img.onload = function() {
                  // ratio of the original image
                  ratio = this.width / this.height;
                  // width and height of the container div of the image
                  var containerWidth = parseInt($(".cropImageWrapper").width());
                  var containerHeight = parseInt($(".cropImageWrapper").height());
                  // if width is bigger than the heigth of the image
                  if(this.width >= this.height){
                    global.width = containerWidth*0.4;
                  }else{// if height is bigger than the width of the image
                     var height = containerHeight*0.3;
                     var heightCalc = containerHeight / height;
                     var calc = containerWidth/heightCalc;
                     global.width = containerWidth/heightCalc*ratio;
                  }
                  // width of the img parent div
                  var wrapperWidth = global.width+13;
                  $(".image-decorator").css("width",wrapperWidth+"px");

                  // set size of the cropped window 80% of the image
                  // set crop window in the middle
                  var cropWindowWidth = Math.round(global.width*0.8);
                  var cropWindowHeight = Math.round(cropWindowWidth / ratio);
                  var x = Math.round(global.width*0.1);
                  var y = Math.round((global.width/ ratio)*0.1);
                  // create crop area
                  $('img#imageToBeCropped').selectAreas({
                        minSize: [10, 10],
                        width: global.width,
                        onChanging : cropChange,
                        areas: [
                            {
                                x: x,
                                y: y,
                                width: cropWindowWidth,
                                height: cropWindowHeight,
                            }
                        ]
                   });

                    var url = global.generateUrl('/'+global.selected_image.url);
                    // set image src for preview
                    $("#imagePrev").css("background-image","url("+url+")");
                    // set crop preview
                    global.cropChange(false, ratio, cropWindowWidth, cropWindowHeight, x, y);

                }

            // this function is called whe crop area is changed
            function cropChange (event, id, areas) {
                global.cropChange(areas, ratio);
			}

            // CROP ******************

            // translations
            this.trans = {
                __title: this.__('media.cropTitle'),
                __cropBtn: this.__('media.cropBtn'),
                __cancelBtn: this.__('base.cancelBtn'),
            };
        },
        props: ['selected_image'],
        methods:{
            // use translation method of vuex
            __(key){
                this.$store.dispatch('__', key);
                return this.getTranslation;
            },
            // when crop btn is clicked
            cropImage(){
                // open loading
                this.$store.dispatch('openLoading');
                // get crop parameters (dimensions)
                var areas = $('img#imageToBeCropped').selectAreas('areas');
                this.height = $('img#imageToBeCropped').height();
                var request = [this.selected_image, areas[0], this.width, this.height, this.app];
                // Get the first 100 results
                this.$http.post(this.basePath+'/'+this.$route.params.adminPrefix+'/media/json/crop-image', request)
                    .then((resp) => {
                        var type = "";
                        var resultMsg = "";
                        if(resp.body == "OK"){
                            type = "success";
                            resultMsg = "Image cropped";
                            new Noty({
                                type: type,
                                layout: 'topRight',
                                text: resultMsg,
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                            // call reset method of parent to reset the list
                            this.reset();
                            this.$store.commit('setIsCropOpen', false);
                        }else{
                            type = "error";
                            resultMsg = "Image could not be cropped";
                            new Noty({
                                type: type,
                                layout: 'topRight',
                                text: resultMsg,
                                timeout: 3000,
                                closeWith: ['button']
                            }).show();
                            this.$store.commit('setIsCropOpen', false);
                        }

                        // close loading
                        this.$store.dispatch('closeLoading');

                    });
            },
            cancelCrop(){
                this.$store.commit('setIsCropOpen', false);
            },
            reset(){
                this.$emit('reset');
            },
            cropChange(areas, ratio, cropWindowWidth, cropWindowHeight, x, y){
                // if this function is called when crop window is changed take changes from "areas" array
                if(areas){
                    var cropWindowWidth = areas[0].width;
                    var cropWindowHeight = areas[0].height;
                    var x = areas[0].x;
                    var y = areas[0].y;
                }
                // the ratio of the crop window
			    var cropWindowRatio = cropWindowWidth / cropWindowHeight;
			    // get the width of the extra space of the image that is not selected
                var notSelectedSpace = this.width - cropWindowWidth;
                // ratio of the space of the image that is not selected
                var notSelectedSpaceRatio = this.width / cropWindowWidth;
                // get width of the preview image container div and calculate his height by using the aspect ratio of the crop window
                var prevWidth = $("#imagePrev").width();
                var prevHeight = prevWidth / cropWindowRatio;
                $("#imagePrev").height(prevHeight);
                // calculate the width of the image in the background
                var bgSize = prevWidth * notSelectedSpaceRatio;
                $("#imagePrev").css("background-size", bgSize+"px");
                // calculate the x of the preview by using the actual width of the crop window, the width of the original image and -
                // the width of the background image of the preview
                var prevX = (x / this.width) * bgSize;
                $("#imagePrev").css("background-position-x", "-"+prevX+"px");
                // get the height of the original image by using the width and the ratio
                var globalHeight = this.width / ratio;
                // get the height of the preview image by using his width and ratio
                var bgSizeHeight = bgSize / ratio;
                // calculate the y of the preview by using the difference of the original image height and the preview heigth
                // and using the original y of the crop window
                var heightDiffRatio = globalHeight / prevHeight;
                var prevY = (y / globalHeight) * bgSizeHeight;
                $("#imagePrev").css("background-position-y", "-"+prevY+"px");
            }
        },
        data(){
            return{
                trans: {},
                width: 600,
                height: '',
                app: 'users'
            }
        },
        computed:{
            // get base url
            basePath(){
                return this.$store.getters.get_base_path;
            }
        }
    }
</script>
