// summary:
//      This dijit works particularly in symbio with 'Sharethis_Form_Configure' form,
//      where it adds drag-and-drop boxes with available and selected services and
//      a 'Generate Key' button to generate a random publisher key.

dojo.provide("p4cms.sharethis.ConfigureForm");
dojo.require("dojo.io.script");
dojo.require("dojo.dnd.Source");
dojo.require("dojo.NodeList-traverse");
dojo.require("dijit.form.Button");
dojo.require("dijit._Widget");

dojo.declare("p4cms.sharethis.ConfigureForm", dijit._Widget, {

    services:       null,

    postCreate: function() {
        // load ShareThis common library (contains list of all available services)
        // and prepare dnd containers with selected and available services
        dojo.io.script.get({
            url:    'http://w.sharethis.com/share5x/js/stcommon.js',
            load:   dojo.hitch(this, function(){
                /*global stlib*/
                this.services = stlib.allServices;
                this._initServiceContainers();
                this._initPublisherKeyButton();

                // if this dijit is inside a dialog, reposition the parent dialog
                // as the form dimensions have changed
                var nodeList      = new dojo.NodeList(this.domNode);
                var closestDialog = nodeList.closest('.dijitDialog');
                if (closestDialog.length) {
                    dijit.byNode(closestDialog[0])._position();
                }
            })
        });
    },

    // initialize containers with available and selected services;
    // services can be moved between containers via drag-and-drop;
    // order of selected services can be changed via drag-and-drop,
    // while order in avalable services container is maintained
    // in an alphabetical order
    _initServiceContainers: function() {
        if (!this.services) {
            return;
        }

        // create container for selected and available services
        var container = dojo.create('div', {'class': 'services-container'});

        // add container to collect selected services
        var selectedServicesWrapper   = dojo.create(
            'div',
            {'class' : 'selected-services-wrapper'},
            container
        );
        var selectedServicesContainer = dojo.create(
            'div',
            {'class' : 'selected-services'},
            selectedServicesWrapper
        );
        dojo.create(
            'div',
            {'class' : 'selected-services-header', innerHTML: 'Selected Services'},
            selectedServicesWrapper,
            'first'
        );

        // add container with all available services
        var availableServicesWrapper   = dojo.create(
            'div',
            {'class' : 'available-services-wrapper'},
            container
        );
        var availableServicesContainer = dojo.create(
            'div',
            {'class' : 'available-services'},
            availableServicesWrapper
        );
        dojo.create(
            'div',
            {'class' : 'available-services-header', innerHTML: 'Available Services'},
            availableServicesWrapper,
            'first'
        );

        // get keys of all available services
        var services = [],
            service;
        for (service in this.services) {
            if (this.services.hasOwnProperty(service)) {
                services.push(service);
            }
        }

        // place service containers before the original services element
        var servicesInput = dojo.query('input[name=services]', this.domNode)[0];
        dojo.place(container, servicesInput, 'before');

        // hide the original services element
        dojo.style(servicesInput, {'display': 'none'});

        // populate selected services container
        var servicesValue     = dojo.attr(servicesInput, 'value');
        var selectedServices  = servicesValue
            ? dojo.map(servicesValue.split(','), dojo.trim)
            : [];
        var dndSelected       = new dojo.dnd.Source(
            selectedServicesContainer,
            {creator: dojo.hitch(this, '_serviceNodeCreator')}
        );
        dndSelected.insertNodes(false, selectedServices);

        // populate available services container
        var availableServices = dojo.filter(services, function(item){
            return dojo.indexOf(selectedServices, item) === -1;
        });
        var dndAvailable      = new dojo.dnd.Source(
            availableServicesContainer,
            {creator: dojo.hitch(this, '_serviceNodeCreator')}
        );
        dndAvailable.insertNodes(false, availableServices);

        // listen to update services element if services selection has changed
        dojo.connect(dndSelected, 'onDrop', dojo.hitch(this, function(){
            this._updateSelectedServices(dndSelected.getAllNodes(), servicesInput);
        }));
        dojo.connect(dndAvailable, 'onDrop', dojo.hitch(this, function(){
            this._updateSelectedServices(dndSelected.getAllNodes(), servicesInput);

            // keep available services in alphabetical order
            var services = dojo.map(dndAvailable.getAllNodes(), function(node){
                return dojo.attr(dojo.query('span.label', node)[0], 'data-service');
            });
            dndAvailable.clearItems();
            dojo.empty(availableServicesContainer);
            dndAvailable.insertNodes(false, services.sort());
        }));

        // subscribe to /dnd/start topic to add class to the avatar
        dojo.subscribe('/dnd/start', function(source){
            if (source === dndSelected || source === dndAvailable) {
                dojo.addClass(dojo.dnd.manager().avatar.node, 'sharethis-avatar');
            }
        });
    },

    // append button to generate a publisher key to the publisher key input
    _initPublisherKeyButton: function() {
        var publisherKeyInput = dojo.query('input[name=publisherKey]', this.domNode)[0];

        // create a button to generate a key
        var button = new dijit.form.Button({
            'label':    'Generate Key',
            'class':    'button-small generate-key-button',
            'onClick':  dojo.hitch(this, function() {
                dojo.attr(publisherKeyInput, 'value', this._generatePublisherKey());
            })
        });

        dojo.place(button.domNode, publisherKeyInput, 'after');
    },

    // custom creator for node with service
    _serviceNodeCreator: function(item, hint) {
        var node  = dojo.create('div', {'class': 'service-container'});
        var label = dojo.isObject(this.services) ? this.services[item].title : item;
        dojo.create('img', {src: 'http://w.sharethis.com/images/' + item + '_32.png'}, node);
        dojo.create('span', {'class': 'label', innerHTML: label, 'data-service' : item}, node);

        return {
            node: node,
            data: item
        };
    },

    // update given element with a string representation of selected services
    // (i.e. contains list of selected services separated by comma)
    _updateSelectedServices: function (nodes, element) {
        var services = dojo.map(nodes, function(node){
            return dojo.attr(dojo.query('span.label', node)[0], 'data-service');
        });

        dojo.attr(element, 'value', services.join(','));
    },

    // return randomly generated publisher key
    _generatePublisherKey: function() {
        var segmentKeys = dojo.map([2, 1, 1, 1, 3], function(segments){
            var i, segmentKey = '';
            for (i = 1; i <= segments; i++) {
                segmentKey += Math.floor(Math.random() * (0xFFFF + 1)).toString(16);
            }
            return segmentKey;
        });

        return 'ch-' + segmentKeys.join('-');
    }
});