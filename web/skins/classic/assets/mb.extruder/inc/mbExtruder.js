/*
 * ******************************************************************************
 *  jquery.mb.components
 *  file: mbExtruder.js
 *
 *  Copyright (c) 2001-2014. Matteo Bicocchi (Pupunzi);
 *  Open lab srl, Firenze - Italy
 *  email: matteo@open-lab.com
 *  site: 	http://pupunzi.com
 *  blog:	http://pupunzi.open-lab.com
 * 	http://open-lab.com
 *
 *  Licences: MIT, GPL
 *  http://www.opensource.org/licenses/mit-license.php
 *  http://www.gnu.org/licenses/gpl.html
 *
 *  last modified: 08/05/14 20.00
 *  *****************************************************************************
 */

(function($) {
	document.extruder=new Object();
	document.extruder.left = 0;
	document.extruder.top = 0;
	document.extruder.bottom = 0;
	document.extruder.right = 0;
	document.extruder.idx=0;

	$.mbExtruder= {
		author:"Matteo Bicocchi & IgorA100",
		version:"2.6.0",
		defaults:{
			width:350,
			noFlap:false,
			positionFixed:true,
/*+*/		selectorResponsiveBlock: null,
/*+*/		attachToParentSide:"left",
/*+*/		bindToButtonByHeight: null,
/*+*/		extruderParentElement: null,
			sensibility:800,
			position:"top",
			accordionPanels:true,
/*~*/		top:"auto", // =topFlap. Для совместимости с прежней версией
/*~*/		topFlap:"auto",
/*~*/		topBlock:0,
			extruderOpacity:1,
			zIndex:'max',
			flapMargin:35,
			textOrientation:"bt", // or "tb" (top-bottom or bottom-top)
			onExtOpen:function(){},
			onExtContentLoad:function(){},
			onExtClose:function(){},
			hidePanelsOnClose:true,
			closeOnClick:true,
			closeOnExternalClick:true,
			autoCloseTime:0,
			autoOpenTime:0,
			slideTimer:300
		},

		buildMbExtruder: function(options){
			return this.each (function (){
				this.options = {};
				$.extend (this.options, $.mbExtruder.defaults);
				$.extend (this.options, options);

				this.idx=document.extruder.idx;
				document.extruder.idx++;
				var extruder,extruderContent,wrapper,extruderStyle,wrapperStyle,txt,closeTimer,openTimer;
				extruder= $(this);
				extruderContent=extruder.html();
/*+*/
				if (this.options.top) this.options.topFlap = this.options.top;
				if (this.options.width > window.innerWidth) {
					this.options.width = window.innerWidth;
				}
/*-*/
				extruder.css("zIndex",100);
				var isVertical = this.options.position=="left" || this.options.position=="right";
				var extW= isVertical?1: this.options.width;
/*+*/			if (this.options.selectorResponsiveBlock) {
/*+*/				var c= $("<div/>").addClass("extruder-content").css({width:extW, display:"none"});
				} else {
					var c= $("<div/>").addClass("extruder-content").css({overflow:"hidden", width:extW, display:"none"});
				}
				c.append(extruderContent);
				extruder.html(c);

				var position=this.options.positionFixed?"fixed":"absolute";
				extruder.addClass("extruder");
				extruder.addClass(this.options.position);
				var isHorizontal = this.options.position=="top" || this.options.position=="bottom";
				extruderStyle =
								this.options.position=="top" ?
				{position:position,top:0,left:"50%",marginLeft:-this.options.width/2,width:this.options.width}:
								this.options.position=="bottom" ?
				{position:position,bottom:0,left:"50%",marginLeft:-this.options.width/2,width:this.options.width}:
								this.options.position=="left" ?
				{position:position,top:this.options.topBlock,left:0,width:1}:
				{position:position,top:this.options.topBlock,right:0,width:1};
				extruder.css(extruderStyle);
				extruder.css({opacity:this.options.extruderOpacity});
				extruder.wrapInner("<div class='extruder-wrapper'></div>");
				wrapper= extruder.find(".extruder-wrapper");

				wrapperStyle={position:"absolute", width:isVertical?1:this.options.width};
				wrapper.css(wrapperStyle);

				if (isHorizontal){
					this.options.position=="top"? document.extruder.top++ : document.extruder.bottom++;
					if (document.extruder.top >1 || document.extruder.bottom>1){
						alert("more than 1 mb.extruder on top or bottom is not supported jet... hope soon!");
						return;
					}
				}

				if ($.metadata){
					$.metadata.setType("class");
					if (extruder.metadata().title) extruder.attr("extTitle",extruder.metadata().title);
					if (extruder.metadata().url) extruder.attr("extUrl",extruder.metadata().url);
					if (extruder.metadata().data) extruder.attr("extData",extruder.metadata().data);
				}

				var flapFooter=$("<div class='footer'/>");
				var flap=$("<div class='flap'><span class='flapLabel'/></div>");
				if (!this.options.noFlap) { /*+*/
					if (document.extruder.bottom){
						wrapper.prepend(flapFooter);
						wrapper.prepend(flap);
					}else{
						wrapper.append(flapFooter);
						wrapper.append(flap);
					}
				}
				txt=extruder.attr("extTitle")?extruder.attr("extTitle"): "";
				var flapLabel = extruder.find(".flapLabel");
				flapLabel.text(txt);
				if(isVertical){
					flapLabel.html(txt).css({whiteSpace:"noWrap"});
					var orientation = this.options.textOrientation == "tb";
/*+*/				flapLabel.css({transform: "rotate(180deg)", writingMode: "vertical-rl"});
				}else{
					flapLabel.html(txt).css({whiteSpace:"noWrap"});
				}

				if (extruder.attr("extUrl")){
					extruder.setMbExtruderContent({
						url:extruder.attr("extUrl"),
						data:extruder.attr("extData"),
						callback: function(){
							if (extruder.get(0).options.onExtContentLoad) extruder.get(0).options.onExtContentLoad();
						}
					})
				}else{
					 /*+*/
					const width = (this.options.noFlap) ? extruder.get(0).options.width : extruder.get(0).options.width-20;
					var container=$("<div>").addClass("text").css({width:width, overflowY:"auto"});
					c.wrapInner(container);
					extruder.setExtruderVoicesAction();
				}

				flap.on("click",function(){
					if (!extruder.attr("isOpened")){
						extruder.openMbExtruder();
					}else{
						extruder.closeMbExtruder();
						//extruder.removeAttr("isOpened");
					}
				}).on("mouseenter",function(){
					if(extruder.get(0).options.autoOpenTime>0){
						openTimer=setTimeout(function(){
							extruder.openMbExtruder();
							$(document).one("click.extruder"+extruder.get(0).idx,function(){extruder.closeMbExtruder();});
						},extruder.get(0).options.autoOpenTime);
					}
				}).on("mouseleave",function(){
					clearTimeout(openTimer);
				});
/*+*/
//ПОКАЗАТЬ/СКРЫТЬ КНОПКОЙ. НАЗНАЧИТЬ НА КНОПКУ.
				if (this.options.bindToButtonByHeight) {
					$(this.options.bindToButtonByHeight).on("click",function(){
						if (!extruder.attr("isOpened")){
							extruder.openMbExtruder();
						}else{
							extruder.closeMbExtruder();
							//extruder.removeAttr("isOpened");
						}
					});
				}
/*-*/
				c.on("mouseleave", function(e){
					if(extruder.get(0).options.closeOnExternalClick){

						//Chrome bug: FORMELEMENT fire mouseleave event.
						if(!$(e.target).parents().is(".text") && !$(e.target).is("select"))
							$(document).one("click.extruder"+extruder.get(0).idx,function(){extruder.closeMbExtruder();});
					}
					closeTimer=setTimeout(function(){

						if(extruder.get(0).options.autoCloseTime > 0){
							extruder.closeMbExtruder();
						}
					},extruder.get(0).options.autoCloseTime);
				}).on("mouseenter", function(){
					clearTimeout(closeTimer);
					$(document).off("click.extruder"+extruder.get(0).idx);
				});

				if (isVertical){
					c.css({ height:"100%"});
					if(this.options.topFlap=="auto") {
						flap.css({top:100+(this.options.position=="left"?document.extruder.left:document.extruder.right)});
						//this.options.position=="left"?document.extruder.left+=labelH+this.options.flapMargin:document.extruder.right+= labelH+this.options.flapMargin;
					}else{
						flap.css({top:this.options.topFlap});
					}
					var clicDiv=$("<div/>").css({position:"absolute",top:0,left:0,width:"100%",height:"100%",background:"transparent"});
					flap.append(clicDiv);
				}

				this.originalWidth = this.options.width;

				$(window).on("resize",function(){ // Это теперь можно убрать ?
					extruder.adjustSize();
				})
/*+*/
				//ОТСЛЕЖИВАЕМ ПРОКРУТКУ МЕНЮ
				if (this.options.bindToButtonByHeight) {
					document.querySelectorAll('.sidebar-layout, .sidebar-content').forEach(function(el) {
						el.addEventListener('scroll', function () {
							extruder.adjustSize();
						});
					});
					//ОТСЛЕЖИВАЕМ ИЗМЕНЕНЕИ РАЗМЕРА (СВОРАЧИВАНИЕ/РАЗВОРАЧИВАНИЕ) МЕНЮ
					const observerLeftSidebar = new window.ResizeObserver(entries => {
						extruder.adjustSize();
					})
					if (this.options.extruderParentElement) {
						observerLeftSidebar.observe(this.options.extruderParentElement);
					}
					extruder.removeClass('hidden-shift'); //ВЕРОЯТНО ЭТО НЕ НУЖНО... НЕТ, НУЖНО !!!
				}
/*-*/
			});
		},

		adjustSize:function(){

			var $extruder = this;
			var extruder = $extruder.get(0);

			var isHorizontal = extruder.options.position=="top" || extruder.options.position=="bottom";

			if(isHorizontal){

				if( $(window).width() < extruder.options.width){
					$extruder.css({width:$(window).width(), marginLeft:0, left:0});
					$extruder.find(".extruder-wrapper, .extruder-content, .extruder-container").css({width:"100%"});
				}else{
					$extruder.css({width:extruder.options.width, left:"50%",marginLeft:-extruder.options.width/2});
					$extruder.find(".extruder-wrapper, .extruder-content, .extruder-container").css({width:"100%"});
				}

			} else{
/*+*/
				if (extruder.options.bindToButtonByHeight) {
					const anchorElement = $j(extruder.options.bindToButtonByHeight);
					const extWrapper = extruder.querySelector('.extruder-wrapper');
					const responsiveBlock = extWrapper.querySelector(extruder.options.selectorResponsiveBlock);
					const responsiveBlockHeight = (responsiveBlock) ? $(responsiveBlock)[0].clientHeight : 0;
					let topPointBlock = 0;

					const anchorOffsetTop = anchorElement.offset().top;
					const bottom = (anchorOffsetTop > 0 ? anchorOffsetTop : 0) + $(extWrapper)[0].clientHeight;//НИЖНЯЯ часть всплывающего блока
					//const heightExtWrapper = $(extWrapper)[0].scrollHeight;

					if (anchorOffsetTop > 0) { //ВСЕ НОРМАЛЬНО, ДВИГАЕМ ВВЕРХ НАШ БЛОК
						topPointBlock = anchorOffsetTop;
					}
					if (bottom > window.innerHeight) { //УПЕРЛИСЬ ВНИЗ
						topPointBlock = window.innerHeight - responsiveBlockHeight;
					}
					if (responsiveBlock) {
						const deltaH = $(extWrapper)[0].clientHeight - responsiveBlockHeight;
						if ($(responsiveBlock)[0].scrollHeight > window.innerHeight - deltaH) { //НЕ ВЛЕЗАЕТ В ОКНО ПО ВЕРТИКАЛИ
							topPointBlock = 0;
							responsiveBlock.style.height = window.innerHeight - deltaH +'px';
							responsiveBlock.style.overflow = 'auto';
						} else {
							responsiveBlock.style.height = 'auto';
							responsiveBlock.style.overflow = 'visible'; // Иначе выпадающие элементы Select не будут выходить за пределы блока.
						}
					}
					extruder.options.topBlock = topPointBlock;
					$extruder.css({top:extruder.options.topBlock});
				}
				if (extruder.options.attachToParentSide == 'right') {
					extruder.options.left = (extruder.options.extruderParentElement) ? $j(extruder.options.extruderParentElement)[0].clientWidth : 0;
					$extruder.css({left:extruder.options.left});
				}
/*-*/
				if( ($(window).width()-80) < extruder.originalWidth){
					extruder.options.width = $(window).width()-80;
				}else{
					extruder.options.width = extruder.originalWidth;
				}

				if($extruder.attr("isOpened")){
					$extruder.find('.extruder-content').css({width: extruder.options.width});
					$extruder.find(".extruder-wrapper, .extruder-content, .extruder-container").css({width:extruder.options.width});
				}
			}

		},

		setMbExtruderContent: function(options){
			this.options = {
				url:false,
				data:"",
				callback:function(){}
			};
			$.extend (this.options, options);
			if (!this.options.url || this.options.url.length==0){
				alert("internal error: no URL to call");
				return;
			}
			var url=this.options.url;
			var data=this.options.data;
			var where=$(this), voice;
			var cb= this.options.callback;
			var container=$("<div>").addClass("extruder-container");
			container.css({width:$(this).get(0).options.width});
			where.find(".extruder-content").wrapInner(container);
			$.ajax({
				type: "GET",
				url: url,
				data: data,
				async:true,
				dataType:"html",
				success: function(html){
					where.find(".extruder-container").html(html);
					voice=where.find(".voice");
					voice.hover(function(){$(this).addClass("hover");},function(){$(this).removeClass("hover");});
					where.setExtruderVoicesAction();
					if (cb) {
						setTimeout(function(){cb();},100);
					}
				}
			});
		},

		openMbExtruder:function(c){
			var extruder= $(this);
/*~*/		//extruder.adjustSize();
			extruder.attr("isOpened",true);
			$(document).off("click.extruder"+extruder.get(0).idx);
			var opt= extruder.get(0).options;
			extruder.addClass("isOpened");
			var position = opt.position; //СТРАННО, НО БЫВАЕТ ОШИБКА "Uncaught TypeError: Cannot read properties of undefined (reading 'position')"
			/* А вот тут не всегда полезно делать максимальный zIndex */
			extruder.mb_bringToFront(opt.zIndex);
			if (position=="top" || position=="bottom"){
/*+*/			extruder.css("left",opt.left);
				extruder.find('.extruder-content').slideDown( opt.slideTimer);
				if(opt.onExtOpen) opt.onExtOpen();
			}else{
/*+*/			extruder.css("top",opt.topBlock);
				//$(this).css("opacity",1);
				extruder.find('.extruder-wrapper').css({width:""});
				//extruder.find('.extruder-content').css({overflowX:"hidden", display:"block"});
				extruder.find('.extruder-content').css({display:"block"});
				//extruder.find('.extruder-content, .extruder-wrapper').animate({ width: opt.width}, opt.slideTimer,function(){ //Так было раньше, но при бейджике он не двигается
				extruder.find('.extruder-content').animate({ width: opt.width}, opt.slideTimer,function(){ // Так сейчас, как повляет на ZM не известно....
					extruder.find(".extruder-container").css({width: opt.width});
				});
				if(opt.onExtOpen) opt.onExtOpen();
			}

			if (c) {
				setTimeout(function(){
					$(document).one("click.extruder"+extruder.get(0).idx,function(){extruder.closeMbExtruder();});
				},100);
			}
			if (opt.bindToButtonByHeight) {
				setTimeout(function(){
					extruder.adjustSize();
				},100);
			}
		},

		closeMbExtruder:function(){
			var extruder= $(this);
			extruder.removeAttr("isOpened");
			var opt= extruder.get(0).options;
			if (!opt) return;
			extruder.removeClass("isOpened");
			$(document).off("click.extruder"+extruder.get(0).idx);
			extruder.css("opacity",opt.extruderOpacity);
			if(opt.hidePanelsOnClose) extruder.hidePanelsOnClose();
			if (opt.position=="top" || opt.position=="bottom"){
				extruder.find('.extruder-content').slideUp(opt.slideTimer);
				if(opt.onExtClose) opt.onExtClose();
			}else if (opt.position=="left" || opt.position=="right"){
//				extruder.find('.extruder-content').css({overflow:"hidden"});
				//extruder.find('.extruder-content, .extruder-wrapper').animate({ width: 1 }, opt.slideTimer,function(){ //Так было раньше, но при бейджике он не двигается
				extruder.find('.extruder-content').animate({ width: 1 }, opt.slideTimer,function(){ // Так сейчас, как повляет на ZM не известно....
					extruder.find('.extruder-wrapper').css({width:1});
//					extruder.find('.extruder-content').css({overflow:"hidden",display:"none"});
					extruder.find('.extruder-content').css({display:"none"});
					if(opt.onExtClose) opt.onExtClose();
				});
			}
		}
	};

	jQuery.fn.mb_bringToFront = function(zIndex){
		if (zIndex == 'max') {
			var zi=10;
			$('*').each(function() {
				if($(this).css("position")=="absolute" || $(this).css("position")=="fixed"){
					const cur = parseInt($(this).css('zIndex'));
					zi = cur > zi ? cur : zi;
				}
			});
			$(this).css('zIndex',zi+=1);
			return zi;
		} else {
			$(this).css('zIndex',zIndex);
			return zIndex;
		}
	};

	/*
	 * EXTRUDER CONTENT
	 */

	$.fn.setExtruderVoicesAction=function(){
		var extruder=$(this);
		var opt=extruder.get(0).options;
		var voices= $(this).find(".voice");
		voices.each(function(){
			var voice=$(this);
			if ($.metadata){
				$.metadata.setType("class");
				if (voice.metadata().panel) voice.attr("panel",voice.metadata().panel);
				if (voice.metadata().data) voice.attr("data",voice.metadata().data);
				if (voice.metadata().disabled) voice.attr("setDisabled", voice.metadata().disabled);
			}

			if (voice.attr("setDisabled"))
				voice.disableExtruderVoice();

			if (voice.attr("panel") && voice.attr("panel")!="false"){
				voice.append("<span class='settingsBtn'/>");
				voice.find(".settingsBtn").css({opacity:.5});
				voice.find(".settingsBtn").hover(
						function(){
							$(this).css({opacity:1});
						},
						function(){
							$(this).not(".sel").css({opacity:.5});
						}).click(function(){
							if ($(this).parents().hasClass("sel")){
								if(opt.accordionPanels)
									extruder.hidePanelsOnClose();
								else
									$(this).closePanel();
								return;
							}

							if(opt.accordionPanels){
								extruder.find(".optionsPanel").slideUp(400,function(){$(this).remove();});
								voices.removeClass("sel");
								voices.find(".settingsBtn").removeClass("sel").css({opacity:.5});
							}
							var content=$("<div class='optionsPanel'></div>");
							voice.after(content);
							$.ajax({
								type: "GET",
								url: voice.attr("panel"),
								data: voice.attr("data"),
								async:true,
								dataType:"html",
								success: function(html){
									var c= $(html);
									content.html(c);
									content.children().not(".text")
											.addClass("panelVoice")
											.click(function(){
												if(opt.closeOnClick)
													extruder.closeMbExtruder();
											});
									content.slideDown(400);
								}
							});
							voice.addClass("sel");
							voice.find(".settingsBtn").addClass("sel").css({opacity:1});
						});
			}

			if (voice.find("a").length==0 && voice.attr("panel")){
				voice.find(".label").not(".disabled").css("cursor","pointer").click(function(){
					voice.find(".settingsBtn").click();
				});
			}

			if ((!voice.attr("panel") || voice.attr("panel")=="false" ) && (!voice.attr("setDisabled") || voice.attr("setDisabled")!="true")){
				voice.find(".label").click(function(){
					extruder.hidePanelsOnClose();
					if(opt.closeOnClick)
						extruder.closeMbExtruder();
				});
			}
		});
	};

	$.fn.disableExtruderVoice=function(){
		var voice=$(this);
		var label = voice.find(".label");
		voice.removeClass("sel");
		voice.next(".optionsPanel").slideUp(400,function(){$(this).remove();});
		voice.attr("setDisabled",true);
		label.css("opacity",.4);
		voice.hover(function(){$(this).removeClass("hover");},function(){$(this).removeClass("hover");});
		label.addClass("disabled").css("cursor","default");
		voice.find(".settingsBtn").hide();
		voice.on("click",function(event){
			event.stopPropagation();
			return false;
		});
	};

	$.fn.enableExtruderVoice=function(){
		var voice=$(this);
		voice.attr("setDisabled",false);
		voice.find(".label").css("opacity",1);
		voice.find(".label").removeClass("disabled").css("cursor","pointer");
		voice.off("click");
		voice.find(".settingsBtn").show();
	};

	$.fn.hidePanelsOnClose=function(){
		var voices= $(this).find(".voice");
		$(this).find(".optionsPanel").slideUp(400,function(){$(this).remove();});
		voices.removeClass("sel");
		voices.find(".settingsBtn").removeClass("sel").css("opacity",.5);
	};

	$.fn.openPanel=function(){
		var voice=$(this).hasClass("voice") ? $(this) : $(this).find(".voice");
		voice.each(function(){
			if($(this).hasClass("sel")) return;
			$(this).find(".settingsBtn").click();
		})
	};

	$.fn.closePanel=function(){
		var voice=$(this).hasClass("voice") ? $(this) : $(this).parent(".voice");
		voice.next(".optionsPanel").slideUp(400,function(){$(this).remove();});
		voice.removeClass("sel");
		$(this).removeClass("sel").css("opacity",.5);
	};

	$.fn.buildMbExtruder=$.mbExtruder.buildMbExtruder;
	$.fn.setMbExtruderContent=$.mbExtruder.setMbExtruderContent;
	$.fn.closeMbExtruder=$.mbExtruder.closeMbExtruder;
	$.fn.openMbExtruder=$.mbExtruder.openMbExtruder;
	$.fn.adjustSize=$.mbExtruder.adjustSize;

})(jQuery);
