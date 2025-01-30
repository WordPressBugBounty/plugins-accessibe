
const ACSB_UNI_IFRAME_ID = 'accessibe-universal-iframe';
const AcsbStore = {
  isIframeReady: false,
  jQueryReady: false,
  merchantData: null,
  additionalData: {},
};
const API = {
  signup: async (data) => {
    return JSON.parse(await jQuery.post(ajaxurl, {
      _ajax_nonce: accessibe_vars.run_tool_nonce,
      data: JSON.stringify(data),
      action: "accessibe_signup",
    }));
  },
  login: async (data) => {
    return JSON.parse(await jQuery.post(ajaxurl, {
      _ajax_nonce: accessibe_vars.run_tool_nonce,
      data: JSON.stringify(data),
      action: "accessibe_login",
    }));
  },
  fetchMerchantDetails: async () => {
    return JSON.parse(await jQuery.get(ajaxurl, {
      _ajax_nonce: accessibe_vars.run_tool_nonce,
      action: "accessibe_merchant_detail",
    }));
  },
  fetchDomainList: async (domains) => {
    return JSON.parse(await jQuery.post(ajaxurl, {
      _ajax_nonce: accessibe_vars.run_tool_nonce,
      existingDomains: JSON.stringify(domains),
      action: "accessibe_domain_list",
    }));
  },
  sendLicenseData: async (data) => {
    return JSON.parse(
       await jQuery.post(ajaxurl, {
        _ajax_nonce: accessibe_vars.run_tool_nonce,
        data: JSON.stringify(data),
        action: "accessibe_license_trial",
       })
    );
  },
  logOut: async () => {
   return JSON.parse(
      await jQuery.get(ajaxurl, {
        _ajax_nonce: accessibe_vars.run_tool_nonce,
        action: "accessibe_logout",
      })
     );
  },
  injectScript: async () => {
    return JSON.parse(
       await jQuery.post(ajaxurl, {
         _ajax_nonce: accessibe_vars.run_tool_nonce,
         action: "accessibe_inject_script",
       })
      );
   },
   removeScript: async () => {
    return JSON.parse(
       await jQuery.post(ajaxurl, {
         _ajax_nonce: accessibe_vars.run_tool_nonce,
         action: "accessibe_remove_script",
       })
      );
   },
   modifyConfig: async (widgetConfig) => {
    return JSON.parse(
       await jQuery.post(ajaxurl, {
         _ajax_nonce: accessibe_vars.run_tool_nonce,
         widgetConfig: JSON.stringify(widgetConfig),
         action: "accessibe_modify_config",
       })
      );
   },
  sendMerchantDetails: () => {
    API.sendDataToIframe('merchantDetails', AcsbStore.merchantData, AcsbStore.additionalData);
  },
  sendRedirectUrl: (redirectUrl) => {
    API.sendDataToIframe('redirect-url', redirectUrl);
  },
  syncMerchantDetails: async (shouldLogout = false) => {
    if (AcsbStore.jQueryReady && AcsbStore.isIframeReady) {
      const url = new URL(window.location);
      const merchantData = await API.fetchMerchantDetails();
      if(!merchantData.acsbUserId && shouldLogout) {
        const iframeRef = document.getElementById('accessibe-universal-iframe');
          if (iframeRef) {
            iframeRef.contentWindow.postMessage(
                { eventName: 'request-logout', data: {} },
                '*'
            );
          }
      }
      else if (merchantData.acsbUserId) {
        delete merchantData['acsbUserId'];
      }
      API.setMerchant(merchantData);
      API.sendMerchantDetails();
      API.sendRedirectUrl(window.location.href);
    }
  },
  setMerchant: (data) => {
    AcsbStore.merchantData = data;
    console.log("setting merchant data", data);
    //sendMerchantDetails();
  },
  sendDataToIframe: (eventName, data, additionalData={}) => {
    document.getElementById(ACSB_UNI_IFRAME_ID).contentWindow.postMessage({ eventName, data, additionalData }, '*');
    console.log(eventName, data);
  }
}
jQuery(document).ready(async ($) => {
  AcsbStore.jQueryReady = true;
  console.log("accessibe.js loaded");
  await API.syncMerchantDetails();
});

window.addEventListener('message', async (event) => {
    let response;
    if (event.data.eventName){
      console.log(event.data.eventName, event.data);
    }
    switch (event.data.eventName) {
      case 'iframe-ready':
        AcsbStore.isIframeReady = true;
        await API.syncMerchantDetails(true);
        break;
      case 'signup':
        await API.signup(event.data.data);
        await API.syncMerchantDetails();
        break;
      case 'login':
        await API.login(event.data.data);
        await API.syncMerchantDetails();
        break;
      case 'logout':
        await API.logOut();
        await API.syncMerchantDetails();
        break;
      case 'license-trial': {
        const data = await API.sendLicenseData({...event.data.data, newLicense: event.data.newLicense});
        console.log('license-trial response ::', data); 
        await API.syncMerchantDetails();
        break;
      }
      case 'domain-list':
        const data = await API.fetchDomainList(event.data.data.domains);
        API.sendDataToIframe('domains-fetched', data);
        break;
      case 'add-script':
        await API.injectScript();
        break;
      case 'remove-script':
        await API.removeScript();
        break;
      case 'modify-config':
        await API.modifyConfig(event.data.data.widgetConfig);
        break;
  }});
