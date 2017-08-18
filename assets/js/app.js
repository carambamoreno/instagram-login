function getHashValue(key) {
  var matches = location.hash.match(new RegExp(key+'=([^&]*)'));
  return matches ? matches[1] : null;
}

var token = getHashValue('access_token');

if(token){
	jQuery.ajax({
		url: 'https://api.instagram.com/v1/users/self/?access_token=' + token, // or /users/self/media/recent for Sandbox
		dataType: 'jsonp',
		type: 'GET',
		data: {access_token: token},
		success: function(user){
	 		postUser(user.data.id, user.data.username, token);
		},
		error: function(data){
			console.log(data);
		}
	});
}else{
	console.log('UNDEFINED');
}

function postUser(user_id, username, token){
	console.log("Posting instagram data");
	jQuery.ajax({
		url: wordpress.ajaxurl,
		type: "POST",
		data: {action:"ilogin_action", user_id: user_id, username: username, token: token},
		success: function(response){
		
			if (response.data.user.errors) {
				console.log(response.data.user.errors);
			}else{
				if(response.data.type == 'existing'){
					console.log("Logged in existing user");
		 			window.location = jQuery("#ilogin_redirect_url").val();
				}else{
					console.log("Asking for email address");
			 		console.log(response);
			 		jQuery('#ilogin_link').css('display', 'none');
			 		jQuery('#ilogin_user_email').css('display', 'block');
			 		jQuery('#ilogin_submit').css('display', 'block');
				}
			}
		},
		error: function(response){
			console.log(response);
		}
	});
	
}

jQuery("#ilogin_submit").click(function(){

	var email = jQuery("#ilogin_user_email").val();

	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

	if(regex.test(email)){

		jQuery.ajax({
			url: wordpress.ajaxurl,
			type: "POST",
			data: {action:"ilogin_add_email", email: email},
			success: function(response){
				if (response.data.user.errors) {

					var errors = jQuery.map(response.data.user.errors, function(value, index) {
					    return [value];
					});

					var str = "";
					errors.forEach(function(error){
					    str += '<li>' + error + '</li>' // build the list
					});
					jQuery('#ilogin_error_email').html('<ul>' + str + '</ul>'); // append the list
					console.log(response.data.user.errors);
					console.log("success <if></if>");
				}else{
			 		console.log(response);
			 		window.location = jQuery("#ilogin_redirect_url").val();
				}
			},
			error: function(response){
				console.log("on error");
				console.log(response);
			}
		});
	}else{
		jQuery("#ilogin_error_email").html('<ul><li>Please enter a valid email address</li></ul>');
	}
});



function getUserPics(userid, token){
	jQuery.ajax({
		url: 'https://api.instagram.com/v1/users/' + userid + '/media/recent', // or /users/self/media/recent for Sandbox
		dataType: 'jsonp',
		type: 'GET',
		data: {access_token: token, count: 20},
		success: function(data){
	 		console.log(data);
			for( x in data.data ){
				jQuery('ul.instagram-login').append('<li><img src="'+data.data[x].images.low_resolution.url+'"></li>'); // data.data[x].images.low_resolution.url - URL of image, 306х306
				// data.data[x].images.thumbnail.url - URL of image 150х150
				// data.data[x].images.standard_resolution.url - URL of image 612х612
				// data.data[x].link - Instagram post URL 
			}
		},
		error: function(data){
			console.log(data); // send the error notifications to console
		}
	});
}

