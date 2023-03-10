/**
 * class for render elements on map
 * @param wrap
 * @constructor
 */
function JSMapContacts(wrap) {
    var $curapp = this;
    $curapp.wrap = wrap;
    $curapp.errorField = '';
    $curapp.offices = {};
    $curapp.spinner = '';

    $curapp.init();
}

/**
 * init class
 */
JSMapContacts.prototype.init =  function () {
    let $curapp = this;
    $curapp.spinner = $curapp.wrap.find('.loader');
    $curapp.errorField = $curapp.wrap.find('.error');
     $curapp.showMap();
}

/**
 * Add script yandex to header
 */
JSMapContacts.prototype.insertYmapScript = function (){

        let $contacts = document.querySelector('.js-map')
        if ($contacts) {
            let contactsTop = $contacts.getBoundingClientRect().top;

            if (contactsTop <= 1000) {
                const key = '2315cab9-5a02-4018-a868-1baea0c875bf';
                const script = document.createElement('script')
                script.setAttribute('type', 'text/javascript')
                script.src = `https://api-maps.yandex.ru/2.1/?apikey=${key}&lang=ru_RU&onload=initYaMaps`

                if (!document.body.classList.contains('ya-maps-added')) {
                    document.head.appendChild(script)
                    document.body.classList.add('ya-maps-added')
                }
            }
        }

}


/**
 * insert in map elements
 */
JSMapContacts.prototype.createMap = function () {
    var $curapp = this;
    window.offices = $curapp.offices;
    window.initYaMaps = function initYaMaps() {


        if ($.isEmptyObject(window.offices) !== 'false') {

            function init() {

                var myMap = new ymaps.Map('map', {
                    center: [50.443705, 30.530946],
                    zoom: 16,
                });

                window.offices.forEach(function (office) {

                    let coords = office.coords.split(',');
                    let name = office.name;
                    let city = office.city;
                    let phone = office.phone;
                    let email = office.email;
                    myMap.geoObjects.add(new ymaps.Placemark(coords, {
                        balloonContentHeader: name,
                        balloonContentBody: city,
                        balloonContentFooter: 'тел: ' + phone + '<br>' + 'email: ' + email,

                        hintContent: name
                    }, {
                        iconLayout: 'default#image',
                        iconImageHref: '/local/sources/img/gazprom-pin.svg',
                        iconImageSize: [50, 50],
                        iconImageOffset: [-25, -45]
                    }))

                    // set size map that be see all elements on map
                    myMap.setBounds(myMap.geoObjects.getBounds());
                });

            }

            ymaps.ready(init);
        }
    }

}



/**
 * render error in field
 * @param $error
 */
JSMapContacts.prototype.setDownloadProccess = function (status){
    var $curapp = this;
    if(status === true){
        $curapp.spinner.fadeOut();
    }else{
        console.log('$curapp.spinner',$curapp.spinner );
        $curapp.spinner.hide();
    }

}
JSMapContacts.prototype.setError = function ($error){
    $('.map-contact__msg').text($error);
}

/**
 * get elements office, render it in map
 * @returns {Promise<void>}
 */
JSMapContacts.prototype.showMap = async function () {
    var $curapp = this;

    let promiseGetOffice = new Promise((resolve, reject) => {

        $.ajax({
            type: "get",
            url: '/local/ajax/get-contacts.php',
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    resolve(data.items);
                } else {
                    reject(data.error);
                }

            }
        });
    });
    promiseGetOffice.then(
        value => {
            $curapp.offices = value
            if (!$.isEmptyObject(value)) {

                window.addEventListener("scroll", function () {
                    $curapp.insertYmapScript();
                });
                $curapp.insertYmapScript();
            }
            $curapp.createMap()

            $curapp.setDownloadProccess(false);
        },

    ).catch( value =>{
        $curapp.setError(value)
        $curapp.setDownloadProccess(false);
    })
}










