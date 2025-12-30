/**
 * Video.js Zoom Rotate Plugin
 * Compatible with Video.js 8.x
 */
(function(window, videojs) {
  'use strict';

  // Default options
  const defaults = {
    zoom: 1,
    rotate: 0
  };

  // Cross-browser CSS transform property detection
  function getTransformProperty() {
    const properties = [
      'transform',
      'WebkitTransform',
      'MozTransform',
      'msTransform',
      'OTransform'
    ];
    
    const testElement = document.createElement('div');
    for (let i = 0; i < properties.length; i++) {
      if (typeof testElement.style[properties[i]] !== 'undefined') {
        return properties[i];
      }
    }
    return 'transform'; // fallback
  }

  /**
   * Plugin function
   */
  function zoomrotate(options) {
    const player = this;
    const settings = videojs.obj.merge(defaults, options);
    
    // Wait for player to be ready
    player.ready(function() {
      const playerEl = player.el();
      const videoEl = playerEl.querySelector('video');
      const posterEl = playerEl.querySelector('.vjs-poster');
      
      if (!videoEl) {
        videojs.log.warn('zoomrotate: video element not found');
        return;
      }

      // Get the appropriate transform property
      const transformProp = getTransformProperty();
      
      // Apply transform
      const transformValue = `scale(${settings.zoom}) rotate(${settings.rotate}deg)`;
      
      // Set overflow hidden on player
      playerEl.style.overflow = 'hidden';
      
      // Apply transform to video element
      videoEl.style[transformProp] = transformValue;
      
      // Apply transform to poster if it exists
      if (posterEl) {
        posterEl.style[transformProp] = transformValue;
      }

      videojs.log('zoomrotate applied:', {
        zoom: settings.zoom,
        rotate: settings.rotate,
        transform: transformValue
      });
    });

    // Store settings on player for potential future access
    player.zoomrotate = {
      zoom: settings.zoom,
      rotate: settings.rotate,
      
      // Method to update zoom/rotate dynamically
      update: function(newZoom, newRotate) {
        const zoom = newZoom !== undefined ? newZoom : this.zoom;
        const rotate = newRotate !== undefined ? newRotate : this.rotate;
        
        const playerEl = player.el();
        const videoEl = playerEl.querySelector('video');
        const posterEl = playerEl.querySelector('.vjs-poster');
        const transformPro      
      // Apply transform to videot transformValue = `scale(${zoom}) rotate(${rotate}deg)`;
        
        if (videoEl) {
          videoEl.style[transformProp] = transformValue;
        }
        if (posterEl) {
          posterEl.style[transformProp] = transformValue;
        }
        
        this.zoom = zoom;
        this.rotate = rotate;
      }
    };
  }

  // Register the      });
    });

    // Store registerPlugin('zoomrotate', zoomrotate);

})(window, window.videojs);
