import { docOn, docOnLoad, on } from "./on.js";
import { $, $$ } from "./selector.js";

docOnLoad(() => {
    $$("." + Zoom.CSS_CONTAINER).forEach($el => {
        const zoom = new Zoom($el);
        zoom.init();
    });
});

class Zoom {
    static RIGHT_CLICK = 2;
    static MIDDLE_CLICK = 1;
    static CSS_CONTAINER = "zoom__container";
    static CSS_ZOOMED = "zoom__container--zoomed";
    static CSS_IMG = "zoom__img";
    static MIN_ZOOMED_DIMENSION = 300;

    constructor($container) {
        this.$container = $container;
        this.$img = null;
        this.imgWidth = 0;
        this.imgHeight = 0;
        this.bigWidth = 0;
        this.bigHeight = 0;
        this.zoomLvl = 1;
        this.enabled = false;
    }

    async init() {
        this.$img = $("." + Zoom.CSS_IMG, this.$container);
        this.setImgWidth();
        await this.loadBigImg();
        this.addListeners();
    }

    async loadBigImg() {
        return new Promise((resolve) => {
            let img = new Image();
            img.onload = () => {
                this.bigWidth = img.width;
                this.bigHeight = img.height;
                resolve();
            };
            if(this.$img.dataset.zoomSrc.length > 0) {
                img.src = this.$img.dataset.zoomSrc;
                this.bigImgUrl = img.src;
            }
            else {
                resolve();
            }
        });
    }

    addListeners() {
        on(this.$container, "contextmenu", e => e.preventDefault());

        on(this.$img, "mousedown", evt => {
            if(this.enabled && evt.button == Zoom.MIDDLE_CLICK) {
                this.zoomLvl = 1;
                this.$container.dispatchEvent(new Event("mousewheel"));
                return;
            }

            if(!this.isRightClick(evt) || !this.isZoomNecessary())
                return;

            this.enabled = true;
            this.$container.classList.add(Zoom.CSS_ZOOMED);
            this.$container.style.backgroundImage = `url(${this.bigImgUrl})`;         
            this.setBackgroundSize();
            this.setBackgroundCoordinates(evt);
        });

        docOn("mouseup", evt => {
            if(this.isRightClick(evt) && this.enabled)
                this.disable();
        });

        on(this.$img, "mousemove", evt => {
            if(this.enabled)
                this.setBackgroundCoordinates(evt);
        });
        
        on(this.$container, "mousewheel", evt => {
            if(!this.enabled)
                return;
            evt.preventDefault();
            if (evt.wheelDelta > 0 || evt.detail < 0) {
                if(this.zoomLvl < 1) {
                    this.zoomLvl += 0.1;
                    this.setBackgroundSize();
                }
            }
            else {
                this.zoomLvl -= 0.1;
                if((this.bigWidth * this.zoomLvl > Zoom.MIN_ZOOMED_DIMENSION) && (this.bigHeight * this.zoomLvl > Zoom.MIN_ZOOMED_DIMENSION))
                    this.setBackgroundSize();
                else
                    this.zoomLvl += 0.1;
            }
        });

        /**
         * Custom event for allowing external code to update image info
         */
        on(this.$container, "zoom:refresh", async () => {
            this.zoomLvl = 1;
            this.setImgWidth();
            await this.loadBigImg();
        });
    }

    async setImgWidth() {
        on(this.$img, "load", () => {
            this.imgWidth = this.$img.width;
            this.imgHeight = this.$img.height;
        });
        if(this.$img.complete) {
            this.imgWidth = this.$img.width;
            this.imgHeight = this.$img.height;
        }
    }

    isRightClick(evt){
        return evt.button == Zoom.RIGHT_CLICK;
    }

    isZoomNecessary(){
        return this.bigWidth > this.imgWidth || this.bigHeight > this.imgHeight;
    }

    setBackgroundSize(){
        this.$container.style.backgroundSize = `${this.bigWidth * this.zoomLvl}px ${this.bigHeight * this.zoomLvl}px`;
    }
    
    disable(){
        this.enabled = false;
        this.$container.classList.remove(Zoom.CSS_ZOOMED);
        this.$container.style.backgroundImage = "none";
    }

    setBackgroundCoordinates(evt){
        if(this.$container.offsetWidth < this.bigWidth)
        {
            const x = Math.round(evt.offsetX/this.imgWidth * 1000) / 1000;
            this.$container.style.backgroundPositionX = (x * 100) + "%";
        }
        else
            this.$container.style.backgroundPositionX = "50%";

        if(this.$container.offsetHeight < this.bigHeight)
        {
            const y = Math.round(evt.offsetY/this.imgHeight * 1000) / 1000;
            this.$container.style.backgroundPositionY = (y * 100) + "%";
        }
        else
            this.$container.style.backgroundPositionY = "50%";
    }
}   