/**
 * Country Flags Utility
 * SVG flags for supported countries
 */

(function(window) {
  'use strict';

  const CountryFlags = {
    /**
     * Get SVG flag for country code
     */
    getSVG: function(countryCode, size = 20) {
      const flags = {
        'SE': `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g clip-path="url(#SE_svg__a)"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12Z" fill="#FFDA44" /><path d="M9.39 10.435h14.508C23.13 4.547 18.096 0 11.999 0c-.896 0-1.768.1-2.608.285v10.15Zm-3.13 0V1.459a12.007 12.007 0 0 0-6.158 8.976H6.26Zm0 3.131H.103A12.007 12.007 0 0 0 6.26 22.54v-8.975Zm3.13 0v10.15c.84.185 1.713.284 2.61.284 6.096 0 11.13-4.547 11.898-10.434H9.39Z" fill="#0052B4"/></g><defs><clipPath id="SE_svg__a"><path fill="#fff" d="M0 0h24v24H0z" /></clipPath></defs></svg>`,
        
        'NO': `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g clip-path="url(#NO_svg__a)"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12Z" fill="#F0F0F0" /><path d="M.413 15.131a12.01 12.01 0 0 0 4.282 6.39v-6.39H.413Zm10.543 8.824c.344.03.692.046 1.043.046 5.545 0 10.21-3.76 11.587-8.87h-12.63v8.824ZM23.586 8.87C22.21 3.76 17.544 0 12 0c-.352 0-.7.017-1.044.046V8.87h12.63ZM4.695 2.48A12.01 12.01 0 0 0 .413 8.87h4.282V2.48Z" fill="#D80027"/><path d="M23.898 10.434H9.391V.284a11.97 11.97 0 0 0-3.13 1.176v8.975H.1a12.104 12.104 0 0 0 0 3.13h6.16v8.976c.97.53 2.021.928 3.13 1.174v-10.15h14.507a12.118 12.118 0 0 0 0-3.13Z" fill="#0052B4"/></g><defs><clipPath id="NO_svg__a"><path fill="#fff" d="M0 0h24v24H0z" /></clipPath></defs></svg>`,
        
        'DK': `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g clip-path="url(#DK_svg__a)"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12Z" fill="#F0F0F0" /><path d="M9.392 10.435h14.507C23.132 4.547 18.097 0 12 0c-.896 0-1.768.1-2.608.285v10.15Zm-3.132.001V1.46a12.008 12.008 0 0 0-6.158 8.976H6.26Zm0 3.13H.103A12.007 12.007 0 0 0 6.26 22.54v-8.975Zm3.132 0v10.15c.84.185 1.712.284 2.608.284 6.097 0 11.132-4.547 11.899-10.434H9.392Z" fill="#D80027"/></g><defs><clipPath id="DK_svg__a"><path fill="#fff" d="M0 0h24v24H0z" /></clipPath></defs></svg>`,
        
        'FI': `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g clip-path="url(#FI_svg__a)"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12Z" fill="#F0F0F0" /><path d="M23.898 10.435H9.391V.285C8.282.531 7.231.93 6.261 1.46v8.976H.1a12.102 12.102 0 0 0 0 3.13h6.16v8.976c.97.53 2.021.928 3.13 1.174v-10.15h14.507a12.121 12.121 0 0 0 0-3.13Z" fill="#0052B4"/></g><defs><clipPath id="FI_svg__a"><path fill="#fff" d="M0 0h24v24H0z" /></clipPath></defs></svg>`,
        
        'CY': `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g clip-path="url(#CY_svg__a)"><path d="M12 24c6.627 0 12-5.373 12-12S18.627 0 12 0 0 5.373 0 12s5.373 12 12 12Z" fill="#FCFCFC" /><path d="M18.783 10.434h-1.565a5.217 5.217 0 1 1-10.435 0H5.218a6.785 6.785 0 0 0 4.93 6.527 1.736 1.736 0 0 0 .182 1.895l1.705-1.367 1.706 1.367c.45-.562.494-1.317.172-1.913a6.785 6.785 0 0 0 4.87-6.509Z" fill="#6DA544"/><path d="M7.826 9.913s0 2.608 2.609 2.608l.522.522H12s.522-1.565 1.565-1.565c0 0 0-1.043 1.044-1.043h1.565s-.522-2.087 2.087-3.653l-1.044-.521s-3.652 2.608-6.26 2.087V9.39H9.913l-.522-.522-1.565 1.044Z" fill="#FFDA44"/></g><defs><clipPath id="CY_svg__a"><path fill="#fff" d="M0 0h24v24H0z" /></clipPath></defs></svg>`
      };
      
      return flags[countryCode] || `<svg width="${size}" height="${size}" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="#f0f0f0" stroke="#ddd"/><text x="12" y="12" text-anchor="middle" dy=".3em" font-size="8" fill="#666">?</text></svg>`;
    },

    /**
     * Get emoji flag for country code (fallback)
     */
    getEmoji: function(countryCode) {
      const flags = {
        'SE': '🇸🇪',
        'NO': '🇳🇴', 
        'DK': '🇩🇰',
        'FI': '🇫🇮',
        'CY': '🇨🇾'
      };
      return flags[countryCode] || '🏳️';
    },

    /**
     * Get country name
     */
    getName: function(countryCode) {
      const names = {
        'SE': 'Sweden',
        'NO': 'Norway',
        'DK': 'Denmark', 
        'FI': 'Finland',
        'CY': 'Cyprus'
      };
      return names[countryCode] || countryCode;
    }
  };

  // Make available globally
  window.CountryFlags = CountryFlags;

})(window);