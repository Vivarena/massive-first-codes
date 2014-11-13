/**
 * Created by Slava Basko
 * Email: basko.slava@gmail.com
 * Date: 6/12/13
 * Time: 1:27 PM
 */

var InfinityScroll = {

    settings: {
        getDataUrl: 'community/getFeeds',
        nextPage: 2,
        dataBlock: $('#ISdataBlock'),
        loader: $('#ISloader')
    },

    privateSettings: {
        _data: null,
        _process: false
    },

    /**
     * Initialize infinity scroll
     * @param options
     */
    init: function(options) {
        this.settings = $.extend(this.settings, options);
        var $this = this;
        $(window).scroll(function(){
            if  (($(window).scrollTop() ) >= ($(document).height() - $(window).height())-100 ){
                $this._process();
            }
        });
    },

    _process: function() {
        var $this = this;
        if($this.privateSettings._process == false) {
            $this.privateSettings._process = true;
            //this._loader('show');
            $this._getData();
            //this._loader('hide');
        }
    },

    _getData: function() {
        var $this = this;

        $.get(this.settings.getDataUrl+'/'+this.settings.nextPage, function(response) {
            if(typeof response !== undefined && response.length > 0) {
                $this.privateSettings._data = response;
                $this._putData();
                return true;
            }else {
                $this._errorHandler();
            }
            return false;
        }, 'json');

        return true;
    },

    _putData: function() {
        var $this = this;
        $this.settings.dataBlock.append($this.privateSettings._data);
        this.settings.nextPage++;
        $this.privateSettings._process = false;
    },

    /**
     * @param action ('hide' or 'show')
     * @private
     */
    _loader: function(action) {
        if(action == 'hide') {

        }else if(action == 'show') {

        }else {

        }
    },

    _errorHandler: function() {
    }

};