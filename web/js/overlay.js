var Overlay = new Class({
  Implements: [Options, Events],
  initialize: function( id, options ) {
    this.setOptions( options );

    this.mask = new Mask( document.body, {'maskMargins': false, 'class': 'overlayMask'} );

    this.id = id?id:'overlay';
    if ( typeOf(this.id) == 'string' ) {
      if ( $(this.id) ) {
        this.element = $(this.id);
      }
    } else {
      this.element = this.id;
      this.id = this.element.get('id');
    }
    if ( !this.element ) {
      this.element = new Element( 'div', {'id': this.id, 'class': 'overlay', 'styles': {'display': 'none'}} );
      if ( this.options.title || this.options.buttons ) {
        var overlayHeader = new Element( 'div', {'class': 'overlayHeader'} );
        if ( this.options.title ) {
          var overlayTitle = new Element( 'div', {'class': 'overlayTitle', 'text': this.options.title} );
          overlayHeader.grab( overlayTitle );
        }
        if ( this.options.buttons ) {
          var overlayToolbar = new Element( 'div', {'class': 'overlayToolbar'} );
          this.options.buttons.each(
              function( button ) {
                var overlayButton = new Element( 'button', {'text': button.text} );
                if ( button.id ) {
                  overlayButton.setProperty( 'id', button.id );
                }
                if ( button.events ) {
                  overlayButton.set( 'events', events );
                }
                overlayToolbar.grab( overlayButton );
              }
          );
          overlayHeader.grab( overlayTitle );
        }
        this.element.grab( overlayHeader );
        var overlayBody = new Element( 'div', {'class': 'overlayBody'} );
        var overlayContent = new Element( 'div', {'class': 'overlayContent'} );
        overlayContent.grab( this.options.content );
        overlayBody.grab( overlayContent );
        this.element.grab( overlayBody );
      }
      this.target = document.id(this.options.target) || document.id(document.body);
      this.element.inject( this.target );
    }
  },
  show: function() {
    this.mask.show();
    window.addEventListener( 'resize', this.update.bind(this), {passive: true} );
    window.addEventListener( 'scroll', this.update.bind(this), {passive: true} );
    this.element.tween( 'opacity', [0, 1.0] );
    this.element.show();
    this.element.position();
    this.mask.position();
  },
  hideComplete: function() {
    this.element.hide();
    this.mask.hide();
  },
  hide: function() {
    new Fx.Tween( this.element, {duration: 400, transition: Fx.Transitions.Sine, onComplete: this.hideComplete.bind(this)} ).start( 'opacity', 1.0, 0 );
  },
  update: function() {
    this.element.position();
    this.mask.position();
  },
  showAnimation: function() {
    showOverlay();

    //console.log( "Showing overlay loading" );
    if ( !this.loading ) {
      this.loading = new Element( 'div', {'id': 'loading'+this.key, 'styles': {'display': 'none'}} );
      this.loading.grab( this.loadingImage );
      document.body.grab( this.loading );
    }
    updateOverlayLoading();
    this.loading.setStyle( 'display', 'block' );
    window.addEventListener( 'resize', this.update.bind(this), {passive: true} );
    window.addEventListener( 'scroll', this.update.bind(this), {passive: true} );
  },
  hideAnimation: function() {
    if ( this.loading ) {
      this.loading.setStyle( 'display', 'none' );
    }
  }
});

function setupOverlays() {
  try {
    $$('.overlay').each(
        function( overlay ) {
          overlay.element = new Overlay( overlay.get('id') );
          overlay.getElements('.overlayCloser').each(
              function( closer ) {
                closer.addEvent( 'click', function() {
                  overlay.element.hide();
                } );
              }
          );
          overlay.overlayShow = function() {
            overlay.element.show();
          };
          overlay.overlayHide = function() {
            overlay.element.hide();
          };
        }
    );
  } catch ( e ) {
    alert( e );
  }
}

window.addEventListener( 'DOMContentLoaded', setupOverlays );
