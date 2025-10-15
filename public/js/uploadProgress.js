/* global Alpine */

import { docOn } from "./on.js";

docOn("alpine:init", () => {
    Alpine.data("uploadProgress", () => ({
        files:[],
        waiting: false,
        get filesDonePercentage() {
            const done = this.files.filter(x => x.done).length;
            return (done / this.files.length) * 100;
        },
        hideTimeout: null,
        init(){
            docOn("uploadprogress:fileadd", e => {
                const id = e.detail;
                this.files.push(new UploadProgress(id));
                clearTimeout(this.hideTimeout);
            });
            docOn("uploadprogress:filedone", e => {
                const id = e.detail;
                const index = this.files.findIndex(element => element.id == id);
                this.files[index].done = true;

                if(this.filesDonePercentage == 100) {
                    this.hideTimeout = setTimeout(this.hide.bind(this), 200);
                }
            });
            docOn("uploadprogress:waitstart", () => {
                this.waiting = true;
            });
            docOn("uploadprogress:waitend", () => {
                this.waiting = false;
            });
        },
        hide(){
            this.files = [];
            this.$dispatch("uploadprogress:alldone");
        }
    }));
});

class UploadProgress
{
    constructor(id, done = false)
    {
        this.id = id;
        this.done = done;
    }
}