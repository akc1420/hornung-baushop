/*
	euroviewer 
	version 18.27
	FISHER-SoftMedia Deutschland
	dialog@fisher-softmedia.de
	
	features
	- swapping up to 72 images
	- scaling 
	- dragging
	- image and document preloader
	- mouse and touchsupport
	- full responsive for tablet, smartphone, pc and mac
	- multibrowser

	uses jquery and native javascript
*/
// fullscreen lib
!function(){"use strict";var a="undefined"==typeof window?{}:window.document,b="undefined"!=typeof module&&module.exports,c="undefined"!=typeof Element&&"ALLOW_KEYBOARD_INPUT"in Element,d=function(){for(var b,c=[["requestFullscreen","exitFullscreen","fullscreenElement","fullscreenEnabled","fullscreenchange","fullscreenerror"],["webkitRequestFullscreen","webkitExitFullscreen","webkitFullscreenElement","webkitFullscreenEnabled","webkitfullscreenchange","webkitfullscreenerror"],["webkitRequestFullScreen","webkitCancelFullScreen","webkitCurrentFullScreenElement","webkitCancelFullScreen","webkitfullscreenchange","webkitfullscreenerror"],["mozRequestFullScreen","mozCancelFullScreen","mozFullScreenElement","mozFullScreenEnabled","mozfullscreenchange","mozfullscreenerror"],["msRequestFullscreen","msExitFullscreen","msFullscreenElement","msFullscreenEnabled","MSFullscreenChange","MSFullscreenError"]],d=0,e=c.length,f={};d<e;d++)if((b=c[d])&&b[1]in a){for(d=0;d<b.length;d++)f[c[0][d]]=b[d];return f}return!1}(),e={request:function(b){var e=d.requestFullscreen;b=b||a.documentElement,/5\.1[.\d]* Safari/.test(navigator.userAgent)?b[e]():b[e](c&&Element.ALLOW_KEYBOARD_INPUT)},exit:function(){a[d.exitFullscreen]()},toggle:function(a){this.isFullscreen?this.exit():this.request(a)},onchange:function(b){a.addEventListener(d.fullscreenchange,b,!1)},onerror:function(b){a.addEventListener(d.fullscreenerror,b,!1)},raw:d};if(!d)return void(b?module.exports=!1:window.screenfull=!1);Object.defineProperties(e,{isFullscreen:{get:function(){return Boolean(a[d.fullscreenElement])}},element:{enumerable:!0,get:function(){return a[d.fullscreenElement]}},enabled:{enumerable:!0,get:function(){return Boolean(a[d.fullscreenEnabled])}}}),b?module.exports=e:window.screenfull=e}();
// end fullscreen lib

// begin var division
var custName = "&copy;&nbsp;hornung-baustoffe.de";
var evVer    = "18.67";

var touchEv = "on";
var contextTag =  "<div id='evContxt'><p onclick='print();'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Drucken...</p><hr id='hlLine'><p id='custName'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+custName+"</p><a href='http://www.fisher-softmedia.de' target='_blank'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Info&nbsp;&uuml;ber&nbsp;euroviewer</a><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Version "+evVer+" HTML5</b></div>";
var scaleIV;
var autoplayClicker = 1;
var touchEv = "on";
var i = 0;
var dbVal = "";
var scaler = iSize;
jQuery.fx.interval = 55;	

// end var division

var BildListe = new Array(); 


var Bilder = new Array();
function imgBuffer() 
{ 
	for ( var i = 0; i <= numPix; i++ )
	{
		if ( i >= 10 ){a = "";};
		if ( i <= 9 ){a = 0;};
		BildListe[i] = "3d_artikel/"+objID+"/desktop/"+objID+"_"+a+i+".jpg"; 
	}

	for ( i = 0; i <= numPix; i++ ) 
	{ 
		Bilder[i] = new Image(); 
		Bilder[i].src = BildListe[i]; 
		var img = $('<img id="imgBuffer'+i+'" style="width:0px;height:0px;">'); 
		img.attr('src', Bilder[i].src );
		img.appendTo('#fsWrapper')
	} 
} 

	
	// begin autoanimation
	 var aniVar;
	 var resetVar;
	 var b = 0;
	 var stpMode;
	 function startAnimation(stpMode)	 
	 { 
		b++;
		if ( b < numPix+2 )
		{
			i++;
			b=0;
			if ( i > numPix ){i = 0;};
		}	
		switch ( stpMode )
		{
			case "one":
				if ( b > (999*2)-2){b=0;};
			break;
			case "endless": 
				if ( b > (numPix*2)-2){b=0;};
			break;
			case "none":
				if ( b > (numPix*2)){b=20;};
			break;
			case "playBTN":
				b=0;
				if ( b > (numPix*2)){b=0;};
			break;
		}
		$("#animationIMG").attr('src', Bilder[i].src);
		currentAction="fsRotate";
	}	
	// end autoanimation

	// begin playloop interval
	playLoop = function (stpMode)
	{
		aniVar = setInterval ( "startAnimation(aSpins)", aSpeed );            
	}
	// end playloop interval
	
	
	// begin set buttons
	function setButtons (fMode,scaler)
	{
		switch ( fMode )
		{
			case "inc":    

				$("#fsResetIMG").attr('src',"template/img/ui/btnReset_inactive.png");

				if ( scaler >= 1.0 )
				{
						currentAction = "fsDrag";
						$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_inactive.png");
						//scaler = 3;
				}
				if ( scaler <= maxZoom-1 )
				{
						currentAction = "fsDrag";
						$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_inactive.png");
				}
				if ( scaler < maxZoom )
				{	
						$("#fsInIMG").attr('src',"template/img/ui/btnZoomIn_active.png");
						$("#fsShiftIMG").attr('src',"template/img/ui/btnShift_active.png");
						$("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_inactive.png");
				}              
			break;
			
			case "dec":	 
				if ( scaler < maxZoom )
				{                
					$("#animationIMG").animate().css({
						'WebkitTransition'  : 'all 500ms linear',
						'MozTransition'     : 'all 500ms linear',
						'MsTransition'      : 'all 500ms linear',
						'OTransition'       : 'all 500ms linear',
						'transition'        : 'all 500ms linear'
					}); 				
				}
				$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_active.png");
				if ( scaler >= 3 )
				{
					$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_active.png");
					currentAction = "fsDrag";
				}
				if ( scaler <= 1.0 )
				{
					currentAction = "fsRotate";
					$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_inactive.png");
					$("#fsShiftIMG").attr('src',"template/img/ui/btnShift_notactive.png");
					scaler = 1.0;
				}
				if ( scaler < 3 )
				{
					$("#fsInIMG").attr('src',"template/img/ui/btnZoomIn_active.png");
				}           
			break;    
		}
	}	
	// end set buttons
	
	// begin setdefaults
	function fsDefaults()
	{
		$("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_active.png");
		$("#fsShiftIMG").attr('src',"template/img/ui/btnShift_notactive.png");
		$("#fsInIMG").attr('src',"template/img/ui/btnZoomIn_inactive.png");
		$("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_notactive.png");
		$("#fsResetIMG").attr('src',"template/img/ui/btnReset_inactive.png");
	   
		// Show first Image if fsReseti
		i=0;
		$("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({
			"position": "relative",
			"left":imgX+"px",
			"top":imgY+"px",
			'WebkitTransition'  : 'all 500ms linear',
			'MozTransition'     : 'all 500ms linear',
			'MsTransition'      : 'all 500ms linear',
			'OTransition'       : 'all 500ms linear',
			'transition'        : 'all 500ms linear',
		});
		setTimeout(function()
		{
			scaleIT( "DEFAULT" );
		}, 1000);
		if ( detailZoomON == "no" )
		{		
			// Scale to initSize if fsReset
			currentAction = "fsRotate";
			setTimeout(function()
			{ 
				var a = 0;
				var i = 0;
				var objIMGTag = $("#animationIMG").attr('src',"3d_artikel/"+objID+"/desktop/"+objID+"_"+a+i+".jpg");
			}, 3000);
		}
	}
	// end setdefaults
	
	// begin mousedirectionmap
	var oldX = 0;
	var oldY = 0;
	var tSense = 0;
	function fsRotateMode_mouse (event) 
	{
		autoplayClicker = 0;		
		b=0;
		$("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
		
		// get delta from direction mousemovement
		var deltaX = oldX - event.clientX,
			deltaY = oldY - event.clientY;
	
		// direction left
		if ( Math.abs ( deltaX ) > Math.abs ( deltaY ) && deltaX > 0 )
		{
			doBrowse("inc");
		}
		
		// direction right
		else if ( Math.abs ( deltaX ) > Math.abs ( deltaY ) &&  deltaX < 0)
		{
			doBrowse("dec");
		}
		
		// direction up
		else if ( Math.abs ( deltaY ) > Math.abs ( deltaX ) && deltaY > 0 )
		{
			// doBrowse ( "incY" );
		}
		// direction down
		else if ( Math.abs ( deltaY ) > Math.abs ( deltaX ) && deltaY < 0 )
		{
			// doBrowse ( "decY" );
		}
		
		// direction up left
		else if ( event.clientX > oldX && event.clientY > oldY ) 
		{
			doBrowse ( "stop" );
		}
		// direction down left
		else if ( event.clientX > oldX && event.clientY < oldY ) 
		{
			doBrowse ( "stop" );
		}
		// direction up right
		else if ( event.clientX < oldX && event.clientY < oldY ) 
		{
			doBrowse ( "stop" );
		}
		// direction up left
		else if ( event.clientX < oldX && event.clientY > oldY ) 
		{
			doBrowse ( "stop" );
		}
		// old mouseposition
		oldX = event.clientX;
		oldY = event.clientY;
	}	
	// end mousedirectionmap

	var toldX;
	// begin touchdirectionmap
	function fsRotateMode_touch ( event )
	{
		// funktion wird direkt im event handler ausgeführt
	}	
	// end touchdirectionmap
	
	// begin sizeContent
	function sizeContent() 
	{
		var newHeight = $("html").height() + "px";
		
		$("#fsWrapper").css("min-height", newHeight);
		$("#fsWrapper").css("min-width", newHeight);
	
		$("#animationIMG").css(
			"position","relative",
			"left",imgX+"px",
			"top",imgY+"px");
		
	}
	// end size content
	
	// begin dobrowse (imageswap)
	function doBrowse(dbVal)
	{
		tSense++;
		if ( tSense > mSense )
		{		
			if ( dbVal == "inc" )
			{
					i++;
					if ( i >= numPix ){i = 0;};
					if ( i < 0 ){i = numPix;};
			}
				
			if ( dbVal == "dec" )
			{
					i--;
					if ( i > numPix ){i = 0;};
					if ( i < 0 ){i = numPix;};
			}
			if ( dbVal == "stop" )
			{
				i=i;
			}
			if ( i >= 10 ){a = "";};
			if ( i <= 9 ){a = 0;};
			$("#animationIMG").attr('src',Bilder[i].src);
			tSense=1;	
		}
	}	
	// end dobrowse (imageswap)
	
	// begin dragimg
	var offsetX = 0;
	var offsetY = 0;
	var dragX = 0;
	var dragY = 0;
	function fsDragIMG_mouse(event)
	{
		  
			$("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({
				'WebkitTransition'  : 'none',
				'MozTransition'     : 'none',
				'MsTransition'      : 'none',
				'OTransition'       : 'none',
				'transition'        : 'none'
			});
			offsetY = (posY - dragY);
			offsetX = (posX - dragX);

			$("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({top: offsetY, left: offsetX});
	 
			$('#fsWrapper').css('cursor', 'pointer'); 
		   
	}
	// end dragimg
	
	
	
// end function division

/*
#######################################################################################################################################################################
#######################################################################################################################################################################
*/

// begin event division
	// begin load event
	$(window).on('load', function() { 
	
		imgBuffer();
		$("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({'display': 'inline'});
		$('#status, #fsWrapper, #animationIMG').fadeOut(); 
		$('#preloader').delay(350).fadeOut('slow'); 
		$('body').delay(350).css({'overflow':'visible'});
		$("#evContxt").hide();
		$("#fsWrapper, #animationIMG, #fsRemote").fadeIn(4500);
		currentAction = "fsRotate";
		aniVar = setTimeout("playLoop()",4500);
	});	
	// end load event

	// begin docready event
	$(document).ready(function () 
	{
		fsDefaults();
		$(document.body).append(contextTag);
		if ( uiButton == "yes" )
		{
			$('#fsRemote').show();
			$("#fsRemote").fadeIn("slow");
		$('#fsRemote').animate({'opacity': '1.0'});	
		}
		if ( uiButton == "no" )
		{
			$('#fsRemote').hide();
			$("#fsRemote").fadeOut(0);
		}
		
		scaleIT(scaler);
		$("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({top: imgY, left: imgX});
		currentAction = "";	
		$("#evContxt").hide();
		sizeContent();
		$(window).resize(sizeContent);
		$("#fsHelper").delay(4550).fadeTo(25,0.6);
		$("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_inactive.png");
        $("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_active.png");
		window.ondragstart = function() { return false; } 
	});	
	// end docready event
// end 	event division
$("#fsRemote").on("touchend touchmove", function (event)
{
    switch ( event.type )
    {
        case "touchmove":
			touchEv = "off";
        break;
        case "touchend":
			touchEv = "on";
        break;
    }
});
// click or touch on fullscreen button enables bigger window
var btnClicker = 1;
$("#btnFullscreen").on ("mouseup touchend mouseover mouseout", function (event){

	switch ( event.type )
	{
		case "mouseover":
            var pos = $('#btnFullscreen').offset();
            $("#ttFullScreen").css({
                position: "fixed",
                top: pos.top+15 + "px",
                left: pos.left+18 + "px",
				opacity:'0.2'
            });
            $("#ttFullScreen").css("visibility","visible"); 
		break;
		case "mouseout":		
            $("#ttFullScreen").css("visibility","hidden"); 
		break;
		case "mouseup":
		case "touchend":
			scaler = iSize;
			btnClicker++;
			if ( btnClicker == 2 )
			{
				btnClicker=0;
			}
			switch ( btnClicker )
			{
				case 0:
				
						currentAction = "fsRotate";
						screenfull.request();
						$('#fsWrapper').css({
							'max-width':'60%',
							'max-height':'85%',
							'border':'1px solid #000000'
						});
						
						$('#fsDetails').css({
							"top":"0px",
							"width":"auto",
							"visibility":"visible",
							"position":"absolute",
							"margin-left":"69%"
						});
						
						$("#animationIMG").css({
							
							'left':imgX+'px',
							'top':imgY+10+'px',
							'-webkit-transform' : 'scale(' + scaler + ')',
							'-moz-transform'    : 'scale(' + scaler + ')',
							'-ms-transform'     : 'scale(' + scaler + ')',
							'-o-transform'      : 'scale(' + scaler + ')',
							'transform'         : 'scale(' + scaler + ')',
							'WebkitTransition'  : 'none',
							'MozTransition'     : 'none',
							'MsTransition'      : 'none',
							'OTransition'       : 'none',
							'transition'        : 'none'
						});					
					 
					$("#btnFullscreen").attr('src',"template/img/ui/btnFullscreen_active.png");
					
				break;
				
				case 1:
					screenfull.exit();
					currentAction = "fsRotate";
					$('#fsDetails').css({
						"top":"0px",
						"width":"auto",
						"visibility":"visible",
						"position":"absolute",
						"margin-left":"780px"
					});
					
					$("#animationIMG").css({
						
						'left':imgX+'px',
						'top':imgY+'px',
						'-webkit-transform' : 'scale(' + scaler + ')',
						'-moz-transform'    : 'scale(' + scaler + ')',
						'-ms-transform'     : 'scale(' + scaler + ')',
						'-o-transform'      : 'scale(' + scaler + ')',
						'transform'         : 'scale(' + scaler + ')',
						'WebkitTransition'  : 'none',
						'MozTransition'     : 'none',
						'MsTransition'      : 'none',
						'OTransition'       : 'none',
						'transition'        : 'none'					
					});
					
					$("#btnFullscreen").attr('src',"template/img/ui/btnFullscreen_inactive.png");	
				break;
			}
		break;
	}
});

var posX = 0;
var posY = 0;
document.onmousemove = function (event) {

  posX = event.clientX;
  posY = event.clientY;  
}

var zoomMax = iSize + maxZoom;
function scaleIT (arg)
{	
    switch (arg)
    {
        case "INC":
            scaler = scaler+0.3;
            setButtons("inc",scaler)
     
        break;
    
        case "DEC":
            var aPos = $('#animationIMG').offset();
            scaler = scaler-0.5;
            setButtons("dec",scaler);
			$("#animationIMG").css({
					'WebkitTransition'  : 'all 500ms linear',
					'MozTransition'     : 'all 500ms linear',
					'MsTransition'      : 'all 500ms linear',
					'OTransition'       : 'all 500ms linear',
					'transition'        : 'all 500ms linear',
					'left'  : imgX+"px",
					'top'   : imgY+"px"  
			});  
        break;
		
		case "DEFAULT":
			scaler = iSize;
		break;
    }  
	
	if ( scaler > zoomMax)
	{
		scaler = zoomMax;
	}
	if ( scaler < iSize )
	{
		scaler = iSize;
	}
    $("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").animate().css({
        'WebkitTransition'  : 'all 500ms linear',
        'MozTransition'     : 'all 500ms linear',
        'MsTransition'      : 'all 500ms linear',
        'OTransition'       : 'all 500ms linear',
        'transition'        : 'all 500ms linear'
    }); 
    $("#animationIMG, #detailZoom_a, #detailZoom_b, #detailZoom_c").css({
      
            '-webkit-transform' : 'scale(' + scaler + ')',
            '-moz-transform'    : 'scale(' + scaler + ')',
            '-ms-transform'     : 'scale(' + scaler + ')',
            '-o-transform'      : 'scale(' + scaler + ')',
            'transform'         : 'scale(' + scaler + ')',
			'border'		    : '0px'

    });     
}

$("#fsPlayIMG").on ( "touchend mouseover mouseout mouseup", function ( event )
{
	switch ( event.type )
	{
		case "mouseover":
            var pos = $('#fsPlayIMG').offset();
            $("#ttAutoPlay").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
            $("#ttAutoPlay").css("visibility","visible");				
		break
		
		case "mouseout":		
            $("#ttAutoPlay").css("visibility","hidden");	
		break;
		
		case "mouseup":
		case "touchend":
			var b = 0;
			var i = 0;
			autoplayClicker++;
			if ( autoplayClicker == 2 )
			{
				autoplayClicker = 0;
			}
			switch ( autoplayClicker )
			{
				case 0:
                    $("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
                    $("#ttAutoPlay").attr('src',"template/img/ui/ttAutoPlay.png");
					clearInterval ( aniVar );
				break;
				
				case 1:
                    $("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_active.png");
                    $("#ttAutoPlay").attr('src',"template/img/ui/ttStopAnimation.png");
					aniVar = setTimeout("playLoop()",25);
				break;	
			
			}
		break;
		
	}
});

$("#fsBrowseIMG").on("touchend mouseover mouseout mouseup", function (event,scaler)
{
    switch ( event.type )
    { 
        case "mouseover":
            $("#ttRotate").css("visibility","visible");
            var pos = $('#fsBrowseIMG').offset();
            $("#ttRotate").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
        break; 
        case "mouseout":
            $("#ttRotate").css("visibility","hidden");          
        break;   
        
        case "mouseup":
        case "touchend":
            currentAction = "fsRotate";
            playModeINC = 0;
			clearTimeout ( aniVar ); 
            $('#fsWrapper').css('cursor', 'move');    
            $("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_active.png");
            $("#fsShiftIMG").attr('src',"template/img/ui/btnShift_inactive.png");
            if ( scaler < 1.0 )
            {
                $("#fsShiftIMG").attr('src',"template/img/ui/btnShift_notactive.png");
            }
            if ( scaler > 1.0 )
            {
                $("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_active.png");
                $("#fsShiftIMG").attr('src',"template/img/ui/btnShift_inactive.png");
                $("#fsInIMG").attr('src',"template/img/ui/btnZoomIn_inactive.png");
                $("#fsOutIMG").attr('src',"template/img/ui/btnZoomOut_inactive.png");
                $("#fsResetIMG").attr('src',"template/img/ui/btnReset_inactive.png");
            }            
        break;    
    }
    
});
$("#fsShiftIMG").on("mouseover mouseout mouseup touchstart touchend touchmove", function (event,scaler)
{

    switch ( event.type )
    { 
        case "mouseover":            
            var pos = $('#fsShiftIMG').offset();
            $("#ttShift").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
            $("#ttShift").css("visibility","visible");
        break; 
        case "mouseout":  
            $("#ttShift").css("visibility","hidden");     
        break;   
        
        case "mouseup":
		case "touchend":
			$("#fsShiftIMG").attr('src',"template/img/ui/btnShift_active.png");
            playModeINC = 0;
            clearTimeout ( aniVar ); 
            $('#fsWrapper').css('cursor', 'move'); 
            currentAction = "fsDrag";
            $("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_inactive.png");
            if ( scaler < 3 )
            {
                currentAction = "fsRotate";
                $("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_inactive.png");
                $("#fsShiftIMG").attr('src',"template/img/ui/btnShift_active.png");
            }
            if ( scaler > 3 )
            {
                currentAction = "fsDrag";
                $("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_inactive.png");
                $("#fsShiftIMG").attr('src',"template/img/ui/btnShift_active.png");
            }           
        break;   
        
    }
    
});
$("#fsInIMG").on("mousedown mouseover mouseout mouseup touchstart touchend touchmove", function (event,scaler)
{
    switch ( event.type )
    { 
        case "mousedown": 
        case "touchstart": 
            scaleIV = setInterval (scaleIT("INC"), scaleIV);   
        break;
		
        
		
        case "mouseover":        
            var pos = $('#fsInIMG').offset();
            $("#ttZoomIn").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
            $("#ttZoomIn").css("visibility", "visible" );          
        break; 
		
        case "mouseout":
            $("#ttZoomIn").css("visibility", "hidden" );       
        break;   
        
        case "mouseup":   
		case "touchend":
            $("#animationIMG").animate().css({
                'WebkitTransition'  : 'none !important',
                'MozTransition'     : 'none !important',
                'MsTransition'      : 'none !important',
                'OTransition'       : 'none !important',
                'transition'        : 'none !important'
            }); 
        break;   
    }
});

$("#fsOutIMG").on("mousedown mouseover mouseout mouseup touchstart touchend touchmove", function (event,scaler)
{
    switch ( event.type )
    {         
        case "mousedown":
            scaleIV = setInterval (scaleIT("DEC"), scaleIV);     
        break;
        
        case "touchstart":
            scaleIV = setInterval (scaleIT("DEC"), scaleIV);     
        break;
        case "mouseover":     
            var pos = $('#fsOutIMG').offset();
            $("#ttZoomOut").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
            $("#ttZoomOut").css("visibility","visible");            
        break; 
        
        case "mouseout":
            $("#ttZoomOut").css("visibility","hidden");     
            clearInterval (scaleIV);
        break;   
        
        case "mouseup":
		case "touchend":
            clearInterval (scaleIV);               
            $("#animationIMG").animate().css({
                'WebkitTransition'  : 'none !important',
                'MozTransition'     : 'none !important',
                'MsTransition'      : 'none !important',
                'OTransition'       : 'none !important',
                'transition'        : 'none !important'
            });        
        break;           
    }
});
detailZoomON="no";
$("#fsResetIMG").on("mouseover mouseout mouseup touchstart touchend", function (event,scaler)
{
    switch ( event.type )
    { 
        case "mouseover":
            var pos = $('#fsResetIMG').offset();
            $("#ttReset").css({
                position: "fixed",
                top: pos.top-50 + "px",
                left: pos.left + "px"
            });
            $("#ttReset").css("visibility","visible");     
        break; 
        case "mouseout":
            $("#ttReset").css("visibility","hidden");     
        break;   
        
        case "mouseup":
            scaler = 1.0;
            fsDefaults();
            $("#fsResetIMG").attr('src',"template/img/ui/btnReset_active.png");         
        break;   
        case "touchend":
            scaler = 1.0;
            fsDefaults();
            $("#fsResetIMG").attr('src',"template/img/ui/btnReset_active.png");         
        break;   
        
    }
});

var toldX;
$("#fsWrapper").on("mouseover mousedown mouseout mouseup mousemove contextmenu touchstart touchmove touchend", function (event)
{
    switch ( event.type )
    {
        case "mouseout":
            removeEventListener('mousemove', fsRotateMode_mouse, false);
            removeEventListener('mousemove', fsDragIMG_mouse, false);   
        break;
        case "mousedown":
			$('#evContxt').hide();
			$('#fsHelper').hide();  
            $("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
		break;
        case "mouseover":  
            $('#fsWrapper').css('cursor', 'pointer'); 
        break;    
        
		case "mouseup":			
            $('#fsWrapper').css('cursor', 'pointer'); 
		break;
		
        case "contextmenu":
            event.preventDefault(); // Nicht entfernen
            x = event.clientX;
            y = event.clientY;
            $("#evContxt").css("left", x + "px");
            $("#evContxt").css("top", y + "px");
            $("#evContxt").show();            
        break;    
        
        case "mousemove":  
        break;    
		
		case "touchstart":	
			$("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
            event.preventDefault();
			$("#fsHelper").hide();
			clearInterval ( aniVar );
			user_active = true;
			var margin_left = parseInt($("#animationIMG").css("margin-left"));
			var margin_top = parseInt($("#animationIMG").css("margin-top"));
			tdragX = (event.originalEvent.touches[0].pageX - divAnimationIMG.offsetLeft)+margin_left;
			tdragY = (event.originalEvent.touches[0].pageY - divAnimationIMG.offsetTop)+margin_top;
		break;				
					
		case "touchmove":
			event.stopPropagation();
			event.preventDefault();
			var tposX = event.originalEvent.touches[0].pageX;
			var tposY = event.originalEvent.touches[0].pageY;				
			$('#evContxt').fadeOut("fast");
		
				switch ( currentAction )
				{
					case "fsRotate":	
						if ( touchEv == "on" )
						{
							$("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
							if ( event.originalEvent.touches[0].pageX < toldX )
							{
								doBrowse("inc");
							}
							if ( event.originalEvent.touches[0].pageX > toldX )
							{
								doBrowse("dec");
							}
							toldX = event.originalEvent.touches[0].pageX;
						}
						if ( touchEv == "off" )
						{
							
						}
					break;
					case "fsDrag":
						var e = event.originalEvent;
						if ( touchEv == "on" )
						{	
							$("#fsPlayIMG").attr('src',"template/img/ui/btnAutoplay_inactive.png");
							toffsetX = (tposX - tdragX);
							toffsetY = (tposY - tdragY);
							$("#animationIMG").css({'top':toffsetY+'px', 'left':toffsetX+'px'});
							$("#animationIMG").css({
								'WebkitTransition'  : 'none',
								'MozTransition'     : 'none',
								'MsTransition'      : 'none',
								'OTransition'       : 'none',
								'transition'        : 'none'
							});
						}
						if ( touchEv == "off" )
						{
							
						}
						$('#fsWrapper').css('cursor', 'move'); 
					break;
				}		
		break;	
		
		case "touchend":
		break;
    }    
});
$("#fsRemote").on("mousemove", function (event)
{
    switch ( event.type )
    {
        case "mousemove":
            removeEventListener('mousemove', fsRotateMode_mouse, false);
            removeEventListener('mousemove', fsDragIMG_mouse, false);
        break;

    }
});

document.onmouseup = function (event) {
	removeEventListener('mousemove', fsRotateMode_mouse, false);
	removeEventListener('mousemove', fsDragIMG_mouse, false);
	dragobjekt=null; 

}

var divAnimationIMG = document.getElementById('animationIMG');
window.onmousedown = function (event) {

  dragX = event.clientX - divAnimationIMG.offsetLeft;
  dragY = event.clientY - divAnimationIMG.offsetTop;
  clearInterval ( aniVar );
  switch ( currentAction )
  {
    case "fsRotate":
        $('#fsWrapper').css('cursor', 'col-resize'); 
		if ( detailZoomON == "yes" )
		{
			removeEventListener('mousemove', fsRotateMode_mouse, false);
			
		}
		if ( detailZoomON == "no" )
		{
			addEventListener('mousemove', fsRotateMode_mouse, false);
		}		
		$("#fsBrowseIMG").attr('src',"template/img/ui/btnBrowse_active.png");
    break;
    case "fsDrag":   	
        addEventListener('mousemove', fsDragIMG_mouse, false);
    break;
  }
}

window.touchmove = function (event) {

	addEventListener('touchmove', fsRotateMode_mouse, false);

}
window.addEventListener('touchmove', function(event) {
 
  event.stopPropagation();
  
}, false); 

window.addEventListener('mousemove', function(event) {
  event.preventDefault();
  event.stopPropagation();
}, false); 


// Fensterhoehe und breite onresize anpassen
$(document).on('mozfullscreenchange webkitfullscreenchange fullscreenchange', function() {
        //console.log('fullscreenchange Event fired');
	
        this.fullScreenMode = !this.fullScreenMode; 
        //console.log('fullScreenMode: ' + this.fullScreenMode);

		
        if (!this.fullScreenMode) {
            //console.log('we are not in fullscreen, do stuff');
			$('#fsWrapper').css({
						'max-width':'800px',
						'min-width':'30%',
						'min-height':'30%',
						'max-height':'80%'
			});	
			btnClicker=1;
        }
});
