(function($) {
    "use strict";

    var fullHeight = function() {
        $('.js-fullheight').css('height', $(window).height());
        $(window).resize(function(){
            $('.js-fullheight').css('height', $(window).height());
        });
    };
    fullHeight();

    $('#sidebarCollapse').on('click', function () {
      $('#sidebar').toggleClass('active');
    });

    $('#profilePicture').on('click', function() {
        $('#profilePictureUpload').click();
    });

    $('#profilePictureUpload').on('change', function() {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#profilePicture').attr('src', e.target.result);
            // Here you would typically send the image data to the server via AJAX to save it
        }
        reader.readAsDataURL(this.files[0]);
    });

})(jQuery);