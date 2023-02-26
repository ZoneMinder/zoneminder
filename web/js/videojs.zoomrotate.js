(function(){
    var defaults, extend;
    defaults = {
      zoom: 1,
      rotate: 0
    };
    extend = function() {
      var args, target, i, object, property;
      args = Array.prototype.slice.call(arguments);
      target = args.shift() || {};
      for (i in args) {
        object = args[i];
        for (property in object) {
          if (object.hasOwnProperty(property)) {
            if (typeof object[property] === 'object') {
              target[property] = extend(target[property], object[property]);
            } else {
              target[property] = object[property];
            }
          }
        }
      }
      return target;
    };

  /**
    * register the zoomrotate plugin
    */
    videojs.plugin('zoomrotate', function(options){
        var settings, player, video, poster;
        settings = extend(defaults, options);

        /* Grab the necessary DOM elements */
        player = this.el();
        video = this.el().getElementsByTagName('video')[0];
        poster = this.el().getElementsByTagName('div')[1]; // div vjs-poster

        console.log('zoomrotate: '+video.style);
        console.log('zoomrotate: '+poster.style);
        console.log('zoomrotate: '+options.rotate);
        console.log('zoomrotate: '+options.zoom);

        /* Array of possible browser specific settings for transformation */
        const properties = ['transform', 'WebkitTransform', 'MozTransform',
                          'msTransform', 'OTransform'];
        prop = properties[0];

        /* Find out which CSS transform the browser supports */
        for (let i=0,len = properties.length;i<len; i++) {
          if (typeof player.style[properties[i]] !== 'undefined') {
            prop = properties[i];
            break;
          }
        }

        /* Let's do it */
        player.style.overflow = 'hidden';
        video.style[prop] = 'scale('+options.zoom+') rotate('+options.rotate+'deg)';
        poster.style[prop] = 'scale('+options.zoom+') rotate('+options.rotate+'deg)';
    });
})();
