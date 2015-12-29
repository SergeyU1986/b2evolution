/**
 *  Sergey's gift div sliping
 */

jQuery(document).ready(function(){
	var index = 0;
	$("#sergey_gift_div .front").css("visibility","visible");
	$("#sergey_gift_div .back").css("visibility","hidden");
	$("#sergey_gift_div .front").addClass("flipper-front");
	$("#sergey_gift_div").click(function(){
		var obj = $(this).children().get(0);
		$("#sergey_gift_div .front").css("visibility","hidden");
		$("#sergey_gift_div .back").css("visibility","hidden");
		if($("#sergey_gift_div .front").hasClass("flipper-front")){
			
			$("#sergey_gift_div .front").removeClass("flipper-front");
			$("#sergey_gift_div .front").addClass("flipper-back");
			$("#sergey_gift_div .front").css("visibility","hidden");
			$("#sergey_gift_div .back").css("visibility","visible");
			$("#sergey_gift_div .back").removeClass("flipper-back");			
			$("#sergey_gift_div .back").addClass("flipper-front");			
		}else{
			
			$("#sergey_gift_div .front").removeClass("flipper-back");
			$("#sergey_gift_div .front").addClass("flipper-front");
			$("#sergey_gift_div .front").css("visibility","visible");
			$("#sergey_gift_div .back").css("visibility","hidden");
			$("#sergey_gift_div .back").removeClass("flipper-front");			
			$("#sergey_gift_div .back").addClass("flipper-back");			
		}
	});
});