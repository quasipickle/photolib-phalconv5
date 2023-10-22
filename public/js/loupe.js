import { docOnLoad } from "./on.js";
import { $$ } from "./selector.js";

docOnLoad(evt => {
    $$(".js-loupe-img").forEach($img => {
       const loupe = new Loupe($img);
       loupe.init();
    });
});

class Loupe {
    constructor($img) {
        this.$img = $img;
        this.bigWidth = 0;
        this.bigHeight = 0;
        this.$loupe = null;
    }

    async init() {
        this.buildLoupe();
        this.$img.parentNode.appendChild(this.$loupe);
        console.log(this.$img.parentNode);
        console.log(this.$loupe)
        await this.loadBigImg()
    }

    buildLoupe()
    {
        this.$loupe = document.createElement('div');
        this.$loupe.classList.add('loupe-widget');
        this.$loupe.style.backgroundImage = `url(${this.$img.dataset.loupeSrc})`; 
    }

    async loadBigImg(){
        return new Promise((resolve, reject) => {
            let img = new Image();
            img.onload = () => {
                this.bigWidth = img.width;
                this.bigHeight = img.height;
                resolve();
            };
            img.src = this.$img.dataset.loupeSrc;
        });
    }


    roundToPlace(num, place) {
        return Math.round(num*Math.pow(10, place))/Math.pow(10, place);
    }
}

function blah(){

      
    $('.js-loupe').each(function() {
      var $this = $(this);
      var $image = $('.js-loupe-image', $this);
      var imgBigUrl = $image.attr('data-image-full');
      var $loupe = $('.js-loupe-element', $this).css('background-image', 'url('+imgBigUrl+')');
      var loupeSize = [$loupe.width(), $loupe.height()];
      var bigImgSize = [0,0];
      var zoomLvl = 1;
      var bigImg = document.createElement('img');
      bigImg.onload = function(){
        bigImgSize = [bigImg.width, bigImg.height]
      }
      bigImg.src = imgBigUrl;
      
      $this.on('mousemove', function(e) {
        var positionRatio = [
          Math.round(e.offsetX/$image.width() * 1000) / 1000,
          Math.round(e.offsetY/$image.height() * 1000) / 1000
        ];
        
        var offset = [
          (loupeSize[0] * positionRatio[0]) - (loupeSize[0] / 2),
          (loupeSize[1] * positionRatio[1]) - (loupeSize[1] / 2),
        ]
        
        var positionPercent = [
          'calc('+positionRatio[0]*100+'% - '+offset[0]+'px)',
          'calc('+positionRatio[1]*100+'% - '+offset[1]+'px)'
        ];
        
        $loupe.css({
          'background-position':  positionPercent.join(' '),
          'top': e.offsetY,
          'left': e.offsetX
        })
      });
      
      $this.on('mousewheel DOMMouseScroll', function(event){
        event.preventDefault();
        if (event.originalEvent.wheelDelta > 0 || event.originalEvent.detail < 0) {
          if(zoomLvl < 1.5) {
            zoomLvl += 0.1;
            $loupe.css('background-size', bigImgSize[0]*zoomLvl+'px '+bigImgSize[1]*zoomLvl+'px');
          }
        }
        else {
            if(zoomLvl > 0.5) {
              zoomLvl -= 0.1;
              $loupe.css('background-size', bigImgSize[0]*zoomLvl+'px '+bigImgSize[1]*zoomLvl+'px');
          }
        }
      });
    });  
      
    };
    