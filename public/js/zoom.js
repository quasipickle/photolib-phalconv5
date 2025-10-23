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
        //this.$img = null;
        this.imgs = [];
        // this.imgWidth = 0;
        // this.imgHeight = 0;
        // this.bigWidth = 0;
        // this.bigHeight = 0;
        this.imgWidths = [];
        this.imgHeights = [];
        this.bigWidths = [];
        this.bigHeights = [];
        this.bigImgUrls = [];
        this.zoomLvl = 1;
        this.enabled = false;
    }

    async init() {
        this.$imgs = $$("." + Zoom.CSS_IMG, this.$container);
        this.setImgWidths();
        await this.loadBigImgs();
        this.addListeners();
    }

    async loadBigImgs() {
        const results = await Promise.all(
            Array.from(this.$imgs).map($img => this.loadBigImg($img))
        );

        this.bigWidths = results.map(r => r?.width);
        this.bigHeights = results.map(r => r?.height);
        this.bigImgUrls = results.map(r => r?.url);
    }

    async loadBigImg($img) {
        return new Promise((resolve) => {
            if(!$img.dataset.zoomSrc?.length)
            {
                resolve(null);
                return;
            }

            let img = new Image();
            img.onload = () => {
                resolve({
                    width: img.width,
                    height: img.height,
                    url: $img.dataset.zoomSrc
                });
            };
            img.onerror = () => resolve(null);
            img.src = $img.dataset.zoomSrc;
        });
    }

    addListeners() {
        on(this.$container, "contextmenu", e => e.preventDefault());

        docOn("mouseup", evt => {
            if(this.isRightClick(evt) && this.enabled)
                this.disable();
        });

        this.$imgs.forEach(($img, index) => {
            on($img, "mousedown", evt => {
                if(this.enabled && evt.button == Zoom.MIDDLE_CLICK) {
                    this.zoomLvl = 1;
                    this.$container.dispatchEvent(new Event("mousewheel"));
                    return;
                }

                if(!this.isRightClick(evt) || !this.isZoomNecessary(index))
                    return;

                this.enabled = true;
                this.$container.classList.add(Zoom.CSS_ZOOMED);
                this.$container.style.backgroundImage = `url(${this.bigImgUrls[0]})`;         
                this.setBackgroundSize(index);
                this.setBackgroundCoordinates(evt, index);
            });

            on($img, "mousemove", evt => {
                if(this.enabled)
                    this.setBackgroundCoordinates(evt, index);
            });

            on($img, "mousewheel", evt => {
                if(!this.enabled)
                    return;
                
                evt.preventDefault();
                if (evt.wheelDelta > 0 || evt.detail < 0) {
                    if(this.zoomLvl < 1) {
                        this.zoomLvl += 0.1;
                        this.setBackgroundSize(index);
                    }
                }
                else {
                    this.zoomLvl -= 0.1;
                    if((this.bigWidths[0] * this.zoomLvl > Zoom.MIN_ZOOMED_DIMENSION) && (this.bigHeights[0] * this.zoomLvl > Zoom.MIN_ZOOMED_DIMENSION))
                        this.setBackgroundSize(index);
                    else
                        this.zoomLvl += 0.1;
                }
            });
        })
        
        

        /**
         * Custom event for allowing external code to update image info
         */
        on(this.$container, "zoom:refresh", async () => {
            this.zoomLvl = 1;
            this.setImgWidths();
            await this.loadBigImgs();
        });
    }

    async setImgWidths() {
        this.$imgs.forEach(($img, index) => {
            on($img, "load", () => {
                this.imgWidths[index] = $img.width;
                this.imgHeights[index] = $img.height;
            });
            if($img.complete) {
                this.imgWidths[index] = $img.width;
                this.imgHeights[index] = $img.height;
            }
        })
    }

    isRightClick(evt){
        return evt.button == Zoom.RIGHT_CLICK;
    }

    isZoomNecessary(index){
        return this.bigWidths[index] > this.imgWidths[index] || this.bigHeights[index] > this.imgHeights[index];
    }

    setBackgroundSize(index){
        this.$container.style.backgroundSize = `${this.bigWidths[index] * this.zoomLvl}px ${this.bigHeights[index] * this.zoomLvl}px`;
    }
    
    disable(){
        this.enabled = false;
        this.$container.classList.remove(Zoom.CSS_ZOOMED);
        this.$container.style.backgroundImage = "none";
    }

    setBackgroundCoordinates(evt, index){
        if(this.$container.offsetWidth < this.bigWidths[index])
        {
            const x = Math.round(evt.offsetX/this.imgWidths[index] * 1000) / 1000;
            this.$container.style.backgroundPositionX = (x * 100) + "%";
        }
        else
            this.$container.style.backgroundPositionX = "50%";

        if(this.$container.offsetHeight < this.bigHeights[index])
        {
            const y = Math.round(evt.offsetY/this.imgHeights[index] * 1000) / 1000;
            this.$container.style.backgroundPositionY = (y * 100) + "%";
        }
        else
            this.$container.style.backgroundPositionY = "50%";
    }
}   