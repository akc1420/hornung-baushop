import ClosePopup from './sisi-search/ClosePopup';
import Filter from './sisi-search/Filter';
import Tracking from './sisi-search/Tracking';
import Paging from './sisi-search/Paging';


// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('ClosePopup', ClosePopup, '.header-main');
PluginManager.register('Filter', Filter, '.is-ctl-search');
PluginManager.register('Tracking', Tracking, 'body');
PluginManager.register('Paging', Paging, '.sisi-get-modus');
