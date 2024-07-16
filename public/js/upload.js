/* global Alpine */

import { on, docOn, off } from "./on.js";
import { post } from "./axios-wrapper.js";
import { $ } from "./selector.js";

docOn("alpine:init", () => {
    Alpine.data("upload", () => ({
        files: new Set(),
        uploadId: 0,
        inPageDragging: false,
        $body: $("body"),
        $grid: $(".album__grid"),
        dropTargetSelector:".drop-target",
        reducer: null,
        init(){
            on(window,"dragstart", this.dragStartHandler.bind(this));
            on(window,"dragenter", this.dragenterHandler.bind(this));
            on(window,"dragleave", this.dragleaveHandler.bind(this));
            on(window,"dragover", this.dragoverHandler.bind(this));
            on(window,"drop", this.dropHandler.bind(this));
        },
        startDownload(){
            const url = prompt("URL to download");
            const data = {
                url: url,
                albumId: window.albumId
            };
            const id = this.uploadId++;
            this.$dispatch("uploadprogress:fileadd", id);
            post("/photo/download", data, `import ${url}`)
                .then(this.reloadWindow);
        },
        filesChoose(){
            this.$refs.fileInput.value = "";
            this.$refs.fileInput.click();
        },
        filesChosen(e){
            this.processFiles(e.target.files);
        },
        processFiles(files){
            for (var file of files){
                this.files.add(file);
                this
                    .upload(file,this.uploadId++)
                    .catch(error => {
                        alert(error);
                    });
            }
        },
        async upload(file, id) {
            this.$dispatch("uploadprogress:fileadd", id);
            if(file.size > window.maxFileSize)
            {
                this.$dispatch("uploadprogress:waitstart");
                file = await this.resizeFile(file);
                this.$dispatch("uploadprogress:waitend");
                if(file.size > window.maxFileSize)
                    return new Promise((resolve, reject) => {
                        reject(`File ${file.name} is still too large, even after resizing to within the configured maximum resize dimension (${window.maxResizeDimension}px), and was not uploaded.`);
                        this.$dispatch("uploadprogress:filedone", id);
                    });
            }

            const formData = new FormData();
            formData.append("albumId", window.albumId);
            formData.append("file", file);
            const uploadPromise = post("/photo/upload", formData, `upload ${file.name}`)
                .then(data => {
                    const template = document.createElement("template");
                    template.innerHTML = data.content.trim();
                    const newNode = template.content.firstElementChild;
                    this.$grid.appendChild(newNode);
                    // this won't work well if the album is so full it has images that haven't loaded yet (lazy)
                    newNode.scrollIntoView(false);
                })
                .finally(() => this.$dispatch("uploadprogress:filedone", id));
            return uploadPromise;
        },

        /**
         * Get the ImageBlobReducer, creating one if it doesn't already exist.
         *
         * @returns ImageBlobReducer
         */
        getReducer(){
            this.reducer = this.reducer ?? new window.ImageBlobReduce({
                pica: window.ImageBlobReduce.pica({features:["js", "wasm"]})
            });
            return this.reducer;
        },

        /**
         * Used to resize a file to within maxFileDimension
         * @param file
         */
        async resizeFile(file){
            const reducer = this.getReducer();
            const blob = await reducer.toBlob(file,{
                max: window.maxResizeDimension,
                unsharpAmount: 80,
                unsharpRadius: 0.6,
                unsharpThreshold: 2
            });

            const newFile = new File([blob], file.name, {type: file.type});

            console.log(newFile);
            return newFile;
        },

        dragStartHandler() {
            if (!this.$store.sorting) {
                this.inPageDragging = true;
            }
        },
        dragenterHandler(e){
            e.preventDefault();
            if(!this.inPageDragging && !this.$store.sorting){
                this.$body.classList.add("show-drop-target");
            }
        },
        dragleaveHandler(e){
            e.preventDefault();
            if(e.target.matches(this.dropTargetSelector)) {
                this.$body.classList.remove("show-drop-target");
            }
        },
        dragoverHandler(e){
            e.preventDefault();
        },
        dropHandler(e){
            e.preventDefault();
            if(!this.inPageDragging)
            {
                this.$body.classList.remove("show-drop-target");
                this.processFiles(e.dataTransfer.files);
            }
            off(this.$body,"mouseleave", this.mouseLeaveListenerFn);
            this.inPageDragging = false;
        }
    }));
});