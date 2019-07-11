import FontFaceObserver from 'fontfaceobserver';

// FontfaceObserver webfont helper script.

// Helper function to transform the raw font data below into a flattened array of FontFaceObserver objects.
function transformFontData(fontData) {
  return fontData.reduce((totalObservers, fontObj) => {
    let currentOberservers = fontObj.styles.reduce((totalObserversStyle, styleObj) => {
      let currentObserversStyle = styleObj.styles.map((style) => {
        return new FontFaceObserver(fontObj.name, {
          weight: styleObj.weight,
          style: style,
        });
      });
      return [...totalObserversStyle, ...currentObserversStyle];
    }, []);
    return [...totalObservers, ...currentOberservers];
  }, []);
}

// CI font
const fontsCi = transformFontData([]);

let fontsCiLoadedPromises = fontsCi.map((fontFaceObserverObj) => {
  // timeout: 7s
  return fontFaceObserverObj.load(null, 7000);
});

// As soon as the fonts are loaded, set a global flag
Promise.all(fontsCiLoadedPromises).then(() => {
  document.documentElement.classList.add('has-loaded-fonts-ci');
});


// Body font
const fontsBody = transformFontData([
  {
    name: 'HelveticaNeueLTPro-Lt',
    styles: [
      {
        weight: 300,
        styles: [
          'normal',
        ],
      },
    ],
  },
]);

let fontsBodyLoadedPromises = fontsBody.map((fontFaceObserverObj) => {
  // timeout: 5s
  return fontFaceObserverObj.load(null, 5000);
});

// As soon as the fonts are loaded, set a global flag
Promise.all(fontsBodyLoadedPromises).then(() => {
  document.documentElement.classList.add('has-loaded-fonts-body');
});
