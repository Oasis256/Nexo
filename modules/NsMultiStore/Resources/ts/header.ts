declare const defineComponent, nsExtraComponents, Axios, __m, multistoreData, popupCloser, popupResolver, Popup;

nsExtraComponents.nsMultiStoreStorePopup    =   defineComponent({
    template: `
    <div class="ns-box multi:shadow-xl multi:w-[95vw] multi:md:w-[66vw] multi:lg:w-[40vw] multi:xl:w-[33vw] multi:h-[95vh] multi:md:h-[66vh] multi:lg:h-[66vh] overflow-hidden flex flex-col">
        <div class="header border-b p-2 flex justify-between items-center ns-box-header">
            <h3 class="font-semibold primary">{{ __m( 'Stores', 'NsMultiStore' ) }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="overflow-y-auto flex-auto ns-box-body">
            <div v-if="hasLoaded === 0" class="h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
            <div v-if="hasLoaded && stores.length === 0" class="h-full w-full flex-col flex items-center justify-center">
                <p>{{ __m( 'No store has been created.', 'NsMultiStore' ) }}</p>
                <p>{{ __m( "Or you don't have a valid access to any store.", 'NsMultiStore' ) }}</p>
            </div>
            <div v-if="hasLoaded && stores.length > 0" class="grid grid-cols-3 w-full">
                <template v-if="subDomainEnabled">
                <a :target="subDomainEnabled ? '__blank' : ''" :href="baseProtocol + store.slug + '.' + baseDomainName" v-for="store of stores" class="border border-box-edge bg-blue-800 cursor-pointer h-40 relative">
                    <div class="h-full w-full object-contain overflow-hidden flex items-center justify-center">
                        <img v-if="store.thumb" :src="store.thumb" :alt="store.name">
                        <img v-if="! store.thumb" class="w-24" :src="'/modules/nsmultistore/assets/images/shop.png'" :alt="store.name">
                    </div>
                    <div class="multi:h-16 bottom-0 multi:absolute multi:w-full multi:z-10 multi:p-2 multi:flex multi:items-center multi:flex-col multi:justify-center store-name multi:font-semibold" style="background: rgb(0,0,0);
background: linear-gradient(0deg, rgba(0,0,0,0.8379726890756303) 0%, rgba(0,0,0,0.7147233893557423) 32%, rgba(0,212,255,0) 100%);">
                        <span class="multi:text-white">{{ store.name }}</span>
                    </div>
                </a>
                </template>
                <template v-else>
                <a :href="baseStoreUrl + '/' + store.id" v-for="store of stores" class="border bg-primary cursor-pointer border-box-edge h-40 relative">
                    <div class="h-full w-full object-contain overflow-hidden flex items-center justify-center">
                        <img v-if="store.thumb" :src="store.thumb" :alt="store.name">
                        <img v-if="! store.thumb" class="w-24" :src="'/modules/nsmultistore/assets/images/shop.png'" :alt="store.name">
                    </div>
                    <div class="multi:h-16 multi:bottom-0 multi:absolute multi:w-full multi:z-10 multi:p-2 multi:flex multi:items-center multi:flex-col multi:justify-center store-name font-semibold" style="background: rgb(0,0,0);
background: linear-gradient(0deg, rgba(0,0,0,0.8379726890756303) 0%, rgba(0,0,0,0.7147233893557423) 32%, rgba(0,212,255,0) 100%);">
                        <span class="multi:text-white">{{ store.name }}</span>
                    </div>
                </a>
                </template>
            </div>
        </div>
        <div class="flex w-full ns-box-footer border-t">
            <div class="flex-auto multi:hover:bg-primary multi:hover:text-white">
                <a :href="multistoreBaseRoute" class="cursor-pointer flex-auto text-2xl h-16 p-2 flex items-center justify-center">
                    <i class="las la-store mr-2"></i>
                    <span>{{ __m( 'Stores', 'NsMultiStore' ) }}</span>
                </a>
            </div>
            <div class="multi:hover:bg-red-500 multi:hover:text-white flex-auto">
                <a class="cursor-pointer text-2xl h-16 p-2 flex items-center justify-center" @click="close()" >
                    <i class="las la-times mr-2"></i>
                    <span>{{ __m( 'Cancel', 'NsMultiStore' ) }}</span>
                </a>
            </div>
        </div>
    </div>
    `,
    props: [ 'popup' ],
    data() {
        return {
            __m,
            popupCloser,
            popupResolver,
            stores: [],
            hasLoaded: false,
            ...multistoreData
        }
    },
    mounted() {
        this.loadStores();
        this.popupCloser();
    },
    methods: {
        close() {
            this.popupResolver(false);
        },
        loadStores() {
            this.hasLoaded      =   false;
            Axios.get( '/api/multistores/stores' )
                .then( result => {
                    this.stores     =   result.data;
                    this.hasLoaded  =   true;
                })
                .catch( ( error ) => {
                    this.hasLoaded  =   true;
                })
        }
    }
});

nsExtraComponents.nsMultistoreSelector  =   defineComponent({
    template: `
    <div @click="toggleStoreSelector()" class="store-selector">
        <span class="hidden md:inline-block px-2">{{ __m( 'Stores', 'NsMultiStore' ) }}</span>
        <div class="px-2">
            <div class="store-count bg-secondary">
                {{ totalStoreCount }}
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            ...multistoreData
        }
    },
    mounted() {

    },
    methods: {
        toggleStoreSelector() {
                    Popup.show( nsExtraComponents.nsMultiStoreStorePopup )
                }
    }
});