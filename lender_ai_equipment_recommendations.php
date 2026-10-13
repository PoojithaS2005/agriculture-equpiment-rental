<?php require_once __DIR__ . '/lender_ai_insights_data.php'; ?><?php
if (!function_exists('lai_page_t')) {
    function lai_page_t(string $text): string {
        $lang = strtolower((string)($GLOBALS['current_lang'] ?? $_GET['lang'] ?? 'en'));
        $translations = [
            'AI-ASSISTED ANALYSIS' => ['kn' => 'AI ನೆರವಿನ ವಿಶ್ಲೇಷಣೆ', 'hi' => 'AI-सहायता प्राप्त विश्लेषण'],
            'Equipment AI Recommendations' => ['kn' => 'ಉಪಕರಣಗಳ AI ಶಿಫಾರಸುಗಳು', 'hi' => 'उपकरण AI सुझाव'],
            'Review demand signals, weather outlook and explainable pricing suggestions.' => ['kn' => 'ಬೇಡಿಕೆಯ ಸೂಚನೆಗಳು, ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ಮತ್ತು ಬೆಲೆ ಸಲಹೆಗಳನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'मांग संकेत, मौसम पूर्वानुमान और समझने योग्य मूल्य सुझाव देखें।'],
            'Back to AI Insights Dashboard' => ['kn' => 'AI ಒಳನೋಟಗಳ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಹಿಂತಿರುಗಿ', 'hi' => 'AI इनसाइट्स डैशबोर्ड पर वापस जाएँ'],
            'Weather-Based Demand Outlook (Next 2 Days)' => ['kn' => 'ಹವಾಮಾನ ಆಧಾರಿತ ಬೇಡಿಕೆ ಮುನ್ಸೂಚನೆ (ಮುಂದಿನ 2 ದಿನಗಳು)', 'hi' => 'मौसम-आधारित मांग पूर्वानुमान (अगले 2 दिन)'],
            'Location is taken automatically from your registered lender profile. This is a rule-based weather impact estimate, not a trained ML prediction or a guarantee of bookings.' => ['kn' => 'ನಿಮ್ಮ ನೋಂದಾಯಿತ ಸಾಲದಾತರ ಪ್ರೊಫೈಲ್‌ನಿಂದ ಸ್ಥಳವನ್ನು ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಪಡೆಯಲಾಗುತ್ತದೆ. ಇದು ನಿಯಮಾಧಾರಿತ ಹವಾಮಾನ ಅಂದಾಜು; ತರಬೇತಿ ಪಡೆದ ML ಮುನ್ಸೂಚನೆ ಅಥವಾ ಬುಕಿಂಗ್ ಖಾತರಿ ಅಲ್ಲ.', 'hi' => 'स्थान आपके पंजीकृत ऋणदाता प्रोफ़ाइल से अपने-आप लिया जाता है। यह नियम-आधारित मौसम अनुमान है, प्रशिक्षित ML पूर्वानुमान या बुकिंग की गारंटी नहीं।'],
            'Forecast location' => ['kn' => 'ಮುನ್ಸೂಚನೆ ಸ್ಥಳ', 'hi' => 'पूर्वानुमान स्थान'],
            'Average rain chance' => ['kn' => 'ಸರಾಸರಿ ಮಳೆಯ ಸಾಧ್ಯತೆ', 'hi' => 'औसत बारिश की संभावना'],
            'Total forecast rainfall' => ['kn' => 'ಒಟ್ಟು ಮುನ್ಸೂಚಿತ ಮಳೆ', 'hi' => 'कुल अनुमानित वर्षा'],
            'Rain probability' => ['kn' => 'ಮಳೆಯ ಸಾಧ್ಯತೆ', 'hi' => 'बारिश की संभावना'],
            'Expected rainfall' => ['kn' => 'ನಿರೀಕ್ಷಿತ ಮಳೆ', 'hi' => 'अनुमानित वर्षा'],
            'Temperature' => ['kn' => 'ತಾಪಮಾನ', 'hi' => 'तापमान'],
            'Weather source: Open-Meteo. Forecasts may change; check local field conditions before making rental decisions.' => ['kn' => 'ಹವಾಮಾನ ಮೂಲ: Open-Meteo. ಮುನ್ಸೂಚನೆ ಬದಲಾಗಬಹುದು; ಬಾಡಿಗೆ ನಿರ್ಧಾರಕ್ಕೂ ಮೊದಲು ಸ್ಥಳೀಯ ಹೊಲದ ಪರಿಸ್ಥಿತಿಯನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'मौसम स्रोत: Open-Meteo। पूर्वानुमान बदल सकते हैं; किराये का निर्णय लेने से पहले स्थानीय खेत की स्थिति जाँचें।'],
            'Smart Rental Pricing & Price History' => ['kn' => 'ಸ್ಮಾರ್ಟ್ ಬಾಡಿಗೆ ಬೆಲೆ ಮತ್ತು ಬೆಲೆ ಇತಿಹಾಸ', 'hi' => 'स्मार्ट किराया मूल्य और मूल्य इतिहास'],
            'Suggested prices use the current average daily price of active equipment in the same category. Price history starts from the setup date\'s baseline and records future price changes automatically.' => ['kn' => 'ಸಲಹೆ ಬೆಲೆಗಳು ಅದೇ ವರ್ಗದ ಸಕ್ರಿಯ ಉಪಕರಣಗಳ ಪ್ರಸ್ತುತ ಸರಾಸರಿ ದೈನಂದಿನ ಬೆಲೆಯನ್ನು ಆಧರಿಸಿವೆ. ಬೆಲೆ ಇತಿಹಾಸವು ಆರಂಭಿಕ ಮೂಲ ಬೆಲೆಯಿಂದ ಪ್ರಾರಂಭವಾಗಿ ಮುಂದಿನ ಬದಲಾವಣೆಗಳನ್ನು ದಾಖಲಿಸುತ್ತದೆ.', 'hi' => 'सुझाए गए मूल्य उसी श्रेणी के सक्रिय उपकरणों के वर्तमान औसत दैनिक मूल्य पर आधारित हैं। मूल्य इतिहास आधार मूल्य से शुरू होकर आगे के बदलाव दर्ज करता है।'],
            'No price history is available yet. Check that the Step 3 SQL setup completed successfully and that equipment exists for your lender account.' => ['kn' => 'ಇನ್ನೂ ಬೆಲೆ ಇತಿಹಾಸ ಲಭ್ಯವಿಲ್ಲ. Step 3 SQL ಸೆಟಪ್ ಯಶಸ್ವಿಯಾಗಿ ಪೂರ್ಣಗೊಂಡಿದೆಯೇ ಮತ್ತು ನಿಮ್ಮ ಖಾತೆಯಲ್ಲಿ ಉಪಕರಣಗಳಿವೆಯೇ ಪರಿಶೀಲಿಸಿ.', 'hi' => 'अभी मूल्य इतिहास उपलब्ध नहीं है। जाँचें कि Step 3 SQL सेटअप पूरा हुआ है और आपके खाते में उपकरण हैं।'],
            'Equipment' => ['kn' => 'ಉಪಕರಣ', 'hi' => 'उपकरण'],
            'Change type' => ['kn' => 'ಬದಲಾವಣೆಯ ಪ್ರಕಾರ', 'hi' => 'बदलाव का प्रकार'],
            'Previous price' => ['kn' => 'ಹಿಂದಿನ ಬೆಲೆ', 'hi' => 'पिछला मूल्य'],
            'New price' => ['kn' => 'ಹೊಸ ಬೆಲೆ', 'hi' => 'नया मूल्य'],
            'Date recorded' => ['kn' => 'ದಾಖಲಿಸಿದ ದಿನಾಂಕ', 'hi' => 'दर्ज करने की तारीख'],
            'Pricing suggestions are reference estimates, not guaranteed market prices. Compare equipment condition, local demand and service costs before changing a rate.' => ['kn' => 'ಬೆಲೆ ಸಲಹೆಗಳು ಅಂದಾಜು ಮಾತ್ರ; ಖಚಿತ ಮಾರುಕಟ್ಟೆ ಬೆಲೆಗಳಲ್ಲ. ದರ ಬದಲಿಸುವ ಮೊದಲು ಉಪಕರಣದ ಸ್ಥಿತಿ, ಸ್ಥಳೀಯ ಬೇಡಿಕೆ ಮತ್ತು ಸೇವಾ ವೆಚ್ಚಗಳನ್ನು ಹೋಲಿಸಿ.', 'hi' => 'मूल्य सुझाव केवल अनुमान हैं, निश्चित बाज़ार मूल्य नहीं। दर बदलने से पहले उपकरण की स्थिति, स्थानीय मांग और सेवा लागत की तुलना करें।'],
            'Equipment Utilization Comparison' => ['kn' => 'ಉಪಕರಣ ಬಳಕೆ ಹೋಲಿಕೆ', 'hi' => 'उपकरण उपयोग तुलना'],
            'No equipment has been added to your account yet.' => ['kn' => 'ನಿಮ್ಮ ಖಾತೆಗೆ ಇನ್ನೂ ಯಾವುದೇ ಉಪಕರಣ ಸೇರಿಸಲಾಗಿಲ್ಲ.', 'hi' => 'आपके खाते में अभी तक कोई उपकरण नहीं जोड़ा गया है।'],
            'Estimated from recorded rental days over a 90-day period.' => ['kn' => '90 ದಿನಗಳ ಅವಧಿಯ ದಾಖಲಾದ ಬಾಡಿಗೆ ದಿನಗಳಿಂದ ಅಂದಾಜಿಸಲಾಗಿದೆ.', 'hi' => '90 दिनों की अवधि में दर्ज किराये के दिनों से अनुमानित।'],
            'This is an approximate indicator, not verified machine operating time.' => ['kn' => 'ಇದು ಅಂದಾಜು ಸೂಚಕ ಮಾತ್ರ; ಯಂತ್ರದ ದೃಢೀಕೃತ ಕಾರ್ಯನಿರ್ವಹಣಾ ಸಮಯವಲ್ಲ.', 'hi' => 'यह अनुमानित संकेतक है, मशीन का सत्यापित संचालन समय नहीं।'],
            'Overall summary:' => ['kn' => 'ಒಟ್ಟಾರೆ ಸಾರಾಂಶ:', 'hi' => 'कुल सारांश:'],
            'Review each equipment item below before changing prices or scheduling maintenance.' => ['kn' => 'ಬೆಲೆ ಬದಲಿಸುವ ಅಥವಾ ನಿರ್ವಹಣೆ ನಿಗದಿಪಡಿಸುವ ಮೊದಲು ಕೆಳಗಿನ ಪ್ರತಿಯೊಂದು ಉಪಕರಣವನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'मूल्य बदलने या रखरखाव तय करने से पहले नीचे प्रत्येक उपकरण देखें।'],
            'No recommendations are available until you add equipment.' => ['kn' => 'ನೀವು ಉಪಕರಣ ಸೇರಿಸುವವರೆಗೆ ಶಿಫಾರಸುಗಳು ಲಭ್ಯವಿರುವುದಿಲ್ಲ.', 'hi' => 'उपकरण जोड़ने तक सुझाव उपलब्ध नहीं होंगे।'],
            'Click to view AI recommendation details' => ['kn' => 'AI ಶಿಫಾರಸು ವಿವರಗಳನ್ನು ನೋಡಲು ಕ್ಲಿಕ್ ಮಾಡಿ', 'hi' => 'AI सुझाव विवरण देखने के लिए क्लिक करें'],
            'Equipment status' => ['kn' => 'ಉಪಕರಣದ ಸ್ಥಿತಿ', 'hi' => 'उपकरण की स्थिति'],
            'Recorded bookings' => ['kn' => 'ದಾಖಲಾದ ಬುಕಿಂಗ್‌ಗಳು', 'hi' => 'दर्ज बुकिंग'],
            'Estimated accumulated rental-days' => ['kn' => 'ಅಂದಾಜು ಒಟ್ಟು ಬಾಡಿಗೆ ದಿನಗಳು', 'hi' => 'अनुमानित कुल किराये के दिन'],
            'Completed / returned rentals' => ['kn' => 'ಪೂರ್ಣಗೊಂಡ / ಹಿಂತಿರುಗಿಸಿದ ಬಾಡಿಗೆಗಳು', 'hi' => 'पूरे हुए / लौटाए गए किराये'],
            'Estimated demand from history' => ['kn' => 'ಇತಿಹಾಸದ ಆಧಾರದ ಬೇಡಿಕೆ ಅಂದಾಜು', 'hi' => 'इतिहास के आधार पर अनुमानित मांग'],
            'Weather-based outlook' => ['kn' => 'ಹವಾಮಾನ ಆಧಾರಿತ ಮುನ್ಸೂಚನೆ', 'hi' => 'मौसम-आधारित अनुमान'],
            'Equipment condition' => ['kn' => 'ಉಪಕರಣದ ಸ್ಥಿತಿ', 'hi' => 'उपकरण की स्थिति'],
            'Customer reviews' => ['kn' => 'ಗ್ರಾಹಕರ ವಿಮರ್ಶೆಗಳು', 'hi' => 'ग्राहक समीक्षाएँ'],
            'No reviews yet' => ['kn' => 'ಇನ್ನೂ ವಿಮರ್ಶೆಗಳಿಲ್ಲ', 'hi' => 'अभी तक कोई समीक्षा नहीं'],
            'Current daily price' => ['kn' => 'ಪ್ರಸ್ತುತ ದೈನಂದಿನ ಬೆಲೆ', 'hi' => 'वर्तमान दैनिक मूल्य'],
            'Category average price' => ['kn' => 'ವರ್ಗದ ಸರಾಸರಿ ಬೆಲೆ', 'hi' => 'श्रेणी का औसत मूल्य'],
            'Price position' => ['kn' => 'ಬೆಲೆಯ ಸ್ಥಾನ', 'hi' => 'मूल्य स्थिति'],
            'AI pricing action' => ['kn' => 'AI ಬೆಲೆ ಸಲಹೆ', 'hi' => 'AI मूल्य सुझाव'],
            'Suggested action price' => ['kn' => 'ಸಲಹೆಯ ಬೆಲೆ', 'hi' => 'सुझाया गया मूल्य'],
            'Why this recommendation?' => ['kn' => 'ಈ ಶಿಫಾರಸು ಏಕೆ?', 'hi' => 'यह सुझाव क्यों?'],
            'Category-based reference price' => ['kn' => 'ವರ್ಗ ಆಧಾರಿತ ಉಲ್ಲೇಖ ಬೆಲೆ', 'hi' => 'श्रेणी-आधारित संदर्भ मूल्य'],
            'Estimated utilization' => ['kn' => 'ಅಂದಾಜು ಬಳಕೆ', 'hi' => 'अनुमानित उपयोग'],
            'Maintenance Guidance' => ['kn' => 'ನಿರ್ವಹಣೆ ಮಾರ್ಗದರ್ಶನ', 'hi' => 'रखरखाव मार्गदर्शन'],
            'Use rental activity as a reminder to inspect equipment and plan routine care.' => ['kn' => 'ಉಪಕರಣ ಪರಿಶೀಲನೆ ಮತ್ತು ನಿಯಮಿತ ಆರೈಕೆ ಯೋಜನೆಗೆ ಬಾಡಿಗೆ ಚಟುವಟಿಕೆಯನ್ನು ನೆನಪಿನ ಸೂಚನೆಯಾಗಿ ಬಳಸಿ.', 'hi' => 'उपकरण की जाँच और नियमित देखभाल की योजना के लिए किराये की गतिविधि को संकेत के रूप में उपयोग करें।'],
            'Important:' => ['kn' => 'ಮುಖ್ಯ:', 'hi' => 'महत्वपूर्ण:'],
            'Usage thresholds are illustrative project rules, not manufacturer service intervals. Use operating-hour records and official service schedules when available.' => ['kn' => 'ಬಳಕೆ ಮಿತಿಗಳು ಯೋಜನೆಯ ಉದಾಹರಣಾತ್ಮಕ ನಿಯಮಗಳು; ತಯಾರಕರ ಸೇವಾ ಅವಧಿಗಳಲ್ಲ. ಲಭ್ಯವಿದ್ದರೆ ಕಾರ್ಯಾಚರಣಾ ಗಂಟೆಗಳ ದಾಖಲೆ ಮತ್ತು ಅಧಿಕೃತ ಸೇವಾ ವೇಳಾಪಟ್ಟಿಯನ್ನು ಬಳಸಿ.', 'hi' => 'उपयोग सीमाएँ परियोजना के उदाहरणात्मक नियम हैं, निर्माता के सर्विस अंतराल नहीं। उपलब्ध होने पर संचालन-घंटे के रिकॉर्ड और आधिकारिक सर्विस शेड्यूल देखें।'],
            'Equipment Maintenance Guidance' => ['kn' => 'ಉಪಕರಣ ನಿರ್ವಹಣೆ ಮಾರ್ಗದರ್ಶನ', 'hi' => 'उपकरण रखरखाव मार्गदर्शन'],
            'No equipment has been added yet.' => ['kn' => 'ಇನ್ನೂ ಯಾವುದೇ ಉಪಕರಣ ಸೇರಿಸಲಾಗಿಲ್ಲ.', 'hi' => 'अभी तक कोई उपकरण नहीं जोड़ा गया है।'],
            'Status' => ['kn' => 'ಸ್ಥಿತಿ', 'hi' => 'स्थिति'],
            'Estimated rental-days' => ['kn' => 'ಅಂದಾಜು ಬಾಡಿಗೆ ದಿನಗಳು', 'hi' => 'अनुमानित किराये के दिन'],
            'Condition' => ['kn' => 'ಸ್ಥಿತಿ', 'hi' => 'स्थिति'],
            'General inspection reminders' => ['kn' => 'ಸಾಮಾನ್ಯ ಪರಿಶೀಲನೆ ನೆನಪಿಗಳು', 'hi' => 'सामान्य निरीक्षण अनुस्मारक'],
            'Check visible damage, tyres, brakes, belts and guards as applicable.' => ['kn' => 'ಅನ್ವಯಿಸಿದಲ್ಲಿ ಗೋಚರ ಹಾನಿ, ಟೈರ್‌ಗಳು, ಬ್ರೇಕ್‌ಗಳು, ಬೆಲ್ಟ್‌ಗಳು ಮತ್ತು ರಕ್ಷಣಾ ಕವಚಗಳನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'जहाँ लागू हो, दिखाई देने वाली क्षति, टायर, ब्रेक, बेल्ट और गार्ड जाँचें।'],
            'Check fluids and leaks for the equipment type.' => ['kn' => 'ಉಪಕರಣದ ಪ್ರಕಾರಕ್ಕೆ ಅನುಗುಣವಾಗಿ ದ್ರವಗಳು ಮತ್ತು ಸೋರಿಕೆಯನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'उपकरण के प्रकार के अनुसार तरल पदार्थ और रिसाव जाँचें।'],
            'Record faults, repairs and service dates after returns.' => ['kn' => 'ಹಿಂತಿರುಗಿಸಿದ ನಂತರ ದೋಷಗಳು, ದುರಸ್ತಿ ಮತ್ತು ಸೇವಾ ದಿನಾಂಕಗಳನ್ನು ದಾಖಲಿಸಿ.', 'hi' => 'वापसी के बाद खराबियाँ, मरम्मत और सर्विस की तारीख दर्ज करें।'],
            'Follow manufacturer guidance and operating-hour intervals.' => ['kn' => 'ತಯಾರಕರ ಮಾರ್ಗದರ್ಶನ ಮತ್ತು ಕಾರ್ಯಾಚರಣಾ ಗಂಟೆಗಳ ಅವಧಿಯನ್ನು ಅನುಸರಿಸಿ.', 'hi' => 'निर्माता के निर्देशों और संचालन-घंटे के अंतराल का पालन करें।'],
            'Resolve under-maintenance status before renting the equipment again.' => ['kn' => 'ಉಪಕರಣವನ್ನು ಮತ್ತೆ ಬಾಡಿಗೆಗೆ ನೀಡುವ ಮೊದಲು ನಿರ್ವಹಣೆ ಬಾಕಿ ಸ್ಥಿತಿಯನ್ನು ಪರಿಹರಿಸಿ.', 'hi' => 'उपकरण को फिर किराये पर देने से पहले लंबित रखरखाव की स्थिति ठीक करें।'],
            'Rental-days are estimated from booking records, not verified machine operating time.' => ['kn' => 'ಬಾಡಿಗೆ ದಿನಗಳನ್ನು ಬುಕಿಂಗ್ ದಾಖಲೆಗಳಿಂದ ಅಂದಾಜಿಸಲಾಗಿದೆ; ಯಂತ್ರದ ದೃಢೀಕೃತ ಕಾರ್ಯಾಚರಣಾ ಸಮಯವಲ್ಲ.', 'hi' => 'किराये के दिनों का अनुमान बुकिंग रिकॉर्ड से है, मशीन के सत्यापित संचालन समय से नहीं।'],
            'Revenue Trends & Estimate' => ['kn' => 'ಆದಾಯದ ಪ್ರವೃತ್ತಿಗಳು ಮತ್ತು ಅಂದಾಜು', 'hi' => 'राजस्व रुझान और अनुमान'],
            'Review recorded completed rental revenue and a simple baseline estimate for the coming month.' => ['kn' => 'ದಾಖಲಾದ ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆ ಆದಾಯ ಮತ್ತು ಮುಂದಿನ ತಿಂಗಳ ಸರಳ ಮೂಲ ಅಂದಾಜನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'दर्ज पूर्ण किराया राजस्व और अगले महीने के सरल आधार अनुमान को देखें।'],
            'Forecast limitation:' => ['kn' => 'ಮುನ್ಸೂಚನೆಯ ಮಿತಿ:', 'hi' => 'पूर्वानुमान की सीमा:'],
            'This baseline averages recent months with completed rental revenue; it is not a trained forecasting model and can be misleading with sparse or seasonal data.' => ['kn' => 'ಈ ಮೂಲ ಅಂದಾಜು ಇತ್ತೀಚಿನ ತಿಂಗಳ ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆ ಆದಾಯದ ಸರಾಸರಿ; ಇದು ತರಬೇತಿ ಪಡೆದ ಮುನ್ಸೂಚನಾ ಮಾದರಿಯಲ್ಲ ಮತ್ತು ಕಡಿಮೆ ಅಥವಾ ಋತುಮಾನಿಕ ಡೇಟಾದಲ್ಲಿ ತಪ್ಪು ದಾರಿ ತೋರಿಸಬಹುದು.', 'hi' => 'यह आधार अनुमान हाल के महीनों के पूर्ण किराया राजस्व का औसत है; यह प्रशिक्षित पूर्वानुमान मॉडल नहीं है और कम या मौसमी डेटा में भ्रामक हो सकता है।'],
            'Total completed rental value' => ['kn' => 'ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆಯ ಒಟ್ಟು ಮೌಲ್ಯ', 'hi' => 'पूर्ण किराये का कुल मूल्य'],
            'Baseline next-month estimate' => ['kn' => 'ಮುಂದಿನ ತಿಂಗಳ ಮೂಲ ಅಂದಾಜು', 'hi' => 'अगले महीने का आधार अनुमान'],
            'Not enough data' => ['kn' => 'ಸಾಕಷ್ಟು ಡೇಟಾ ಇಲ್ಲ', 'hi' => 'पर्याप्त डेटा नहीं'],
            'Completed Rental Revenue — Last 6 Months' => ['kn' => 'ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆ ಆದಾಯ — ಕಳೆದ 6 ತಿಂಗಳು', 'hi' => 'पूर्ण किराया राजस्व — पिछले 6 महीने'],
            'Revenue grouped by booking start month for completed/returned bookings; it may differ from settled payments or accounting records.' => ['kn' => 'ಪೂರ್ಣಗೊಂಡ/ಹಿಂತಿರುಗಿಸಿದ ಬುಕಿಂಗ್‌ಗಳಿಗೆ ಪ್ರಾರಂಭದ ತಿಂಗಳ ಪ್ರಕಾರ ಆದಾಯವನ್ನು ಗುಂಪು ಮಾಡಲಾಗಿದೆ; ಇದು ಪಾವತಿ ಅಥವಾ ಲೆಕ್ಕಪತ್ರ ದಾಖಲೆಗಳಿಂದ ಭಿನ್ನವಾಗಿರಬಹುದು.', 'hi' => 'पूर्ण/लौटाए गए बुकिंग का राजस्व शुरू होने के महीने के अनुसार है; यह भुगतान या लेखांकन रिकॉर्ड से अलग हो सकता है।'],
            'Monthly breakdown' => ['kn' => 'ತಿಂಗಳ ವಿವರ', 'hi' => 'मासिक विवरण'],
            'Month' => ['kn' => 'ತಿಂಗಳು', 'hi' => 'महीना'],
            'Recorded rental value' => ['kn' => 'ದಾಖಲಾದ ಬಾಡಿಗೆ ಮೌಲ್ಯ', 'hi' => 'दर्ज किराया मूल्य'],
            'Review Analysis' => ['kn' => 'ವಿಮರ್ಶೆ ವಿಶ್ಲೇಷಣೆ', 'hi' => 'समीक्षा विश्लेषण'],
            'Summarize customer ratings and flag equipment where feedback may need attention.' => ['kn' => 'ಗ್ರಾಹಕರ ರೇಟಿಂಗ್‌ಗಳನ್ನು ಸಂಕ್ಷಿಪ್ತಗೊಳಿಸಿ ಮತ್ತು ಗಮನ ಅಗತ್ಯವಿರುವ ಉಪಕರಣಗಳನ್ನು ಗುರುತಿಸಿ.', 'hi' => 'ग्राहक रेटिंग का सारांश बनाएँ और उन उपकरणों को चिह्नित करें जिनकी प्रतिक्रिया पर ध्यान देना चाहिए।'],
            'Review ratings and written comments are shown below each equipment item when available.' => ['kn' => 'ಲಭ್ಯವಿದ್ದಲ್ಲಿ ಪ್ರತಿ ಉಪಕರಣದ ಕೆಳಗೆ ರೇಟಿಂಗ್ ಮತ್ತು ಲಿಖಿತ ಪ್ರತಿಕ್ರಿಯೆಗಳನ್ನು ತೋರಿಸಲಾಗುತ್ತದೆ.', 'hi' => 'उपलब्ध होने पर हर उपकरण के नीचे रेटिंग और लिखित टिप्पणियाँ दिखाई जाती हैं।'],
            'Customer Feedback by Equipment' => ['kn' => 'ಉಪಕರಣದ ಪ್ರಕಾರ ಗ್ರಾಹಕರ ಪ್ರತಿಕ್ರಿಯೆ', 'hi' => 'उपकरण के अनुसार ग्राहक प्रतिक्रिया'],
            'Average rating' => ['kn' => 'ಸರಾಸರಿ ರೇಟಿಂಗ್', 'hi' => 'औसत रेटिंग'],
            'Total reviews' => ['kn' => 'ಒಟ್ಟು ವಿಮರ್ಶೆಗಳು', 'hi' => 'कुल समीक्षाएँ'],
            'Low ratings (1–2)' => ['kn' => 'ಕಡಿಮೆ ರೇಟಿಂಗ್‌ಗಳು (1–2)', 'hi' => 'कम रेटिंग (1–2)'],
            'Positive ratings (4–5)' => ['kn' => 'ಸಕಾರಾತ್ಮಕ ರೇಟಿಂಗ್‌ಗಳು (4–5)', 'hi' => 'सकारात्मक रेटिंग (4–5)'],
            'No review evidence yet; collect feedback after future rentals.' => ['kn' => 'ಇನ್ನೂ ವಿಮರ್ಶೆಗಳಿಲ್ಲ; ಮುಂದಿನ ಬಾಡಿಗೆಗಳ ನಂತರ ಪ್ರತಿಕ್ರಿಯೆ ಸಂಗ್ರಹಿಸಿ.', 'hi' => 'अभी समीक्षा उपलब्ध नहीं; भविष्य के किराये के बाद प्रतिक्रिया लें।'],
            'Only a few reviews are available; read comments manually before drawing conclusions.' => ['kn' => 'ಕೆಲವೇ ವಿಮರ್ಶೆಗಳಿವೆ; ತೀರ್ಮಾನಿಸುವ ಮೊದಲು ಕಾಮೆಂಟ್‌ಗಳನ್ನು ಓದಿ.', 'hi' => 'केवल कुछ समीक्षाएँ हैं; निष्कर्ष निकालने से पहले टिप्पणियाँ पढ़ें।'],
            'Review recent comments, identify repeated concerns, and check equipment condition and handover quality.' => ['kn' => 'ಇತ್ತೀಚಿನ ಕಾಮೆಂಟ್‌ಗಳನ್ನು ಪರಿಶೀಲಿಸಿ, ಮರುಕಳಿಸುವ ಸಮಸ್ಯೆಗಳನ್ನು ಗುರುತಿಸಿ ಮತ್ತು ಉಪಕರಣದ ಸ್ಥಿತಿ ಹಾಗೂ ಹಸ್ತಾಂತರದ ಗುಣಮಟ್ಟ ಪರಿಶೀಲಿಸಿ.', 'hi' => 'हाल की टिप्पणियाँ देखें, बार-बार आने वाली समस्याएँ पहचानें और उपकरण की स्थिति व सौंपने की गुणवत्ता जाँचें।'],
            'Feedback is strong so far; maintain equipment condition and consistent service.' => ['kn' => 'ಇಲ್ಲಿಯವರೆಗೆ ಪ್ರತಿಕ್ರಿಯೆ ಉತ್ತಮವಾಗಿದೆ; ಉಪಕರಣದ ಸ್ಥಿತಿ ಮತ್ತು ಸೇವೆಯ ಗುಣಮಟ್ಟ ಕಾಪಾಡಿ.', 'hi' => 'अब तक प्रतिक्रिया अच्छी है; उपकरण की स्थिति और सेवा की गुणवत्ता बनाए रखें।'],
            'Monitor new feedback and look for recurring suggestions.' => ['kn' => 'ಹೊಸ ಪ್ರತಿಕ್ರಿಯೆಗಳನ್ನು ಗಮನಿಸಿ ಮತ್ತು ಮರುಕಳಿಸುವ ಸಲಹೆಗಳನ್ನು ಪರಿಶೀಲಿಸಿ.', 'hi' => 'नई प्रतिक्रिया देखें और बार-बार आने वाले सुझावों पर ध्यान दें।'],
            'No written reviews are available for this equipment yet.' => ['kn' => 'ಈ ಉಪಕರಣಕ್ಕೆ ಇನ್ನೂ ಲಿಖಿತ ವಿಮರ್ಶೆಗಳಿಲ್ಲ.', 'hi' => 'इस उपकरण के लिए अभी लिखित समीक्षाएँ उपलब्ध नहीं हैं।'],
            'Customer' => ['kn' => 'ಗ್ರಾಹಕ', 'hi' => 'ग्राहक'],
            'No written comment was provided.' => ['kn' => 'ಲಿಖಿತ ಕಾಮೆಂಟ್ ನೀಡಲಾಗಿಲ್ಲ.', 'hi' => 'कोई लिखित टिप्पणी नहीं दी गई।'],
            'Agriculture Equipment Rental System' => ['kn' => 'ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ', 'hi' => 'कृषि उपकरण किराया प्रणाली'],
            'Estimated utilization (%)' => ['kn' => 'ಅಂದಾಜು ಬಳಕೆ (%)', 'hi' => 'अनुमानित उपयोग (%)'],
            'Recorded rental value (₹)' => ['kn' => 'ದಾಖಲಾದ ಬಾಡಿಗೆ ಮೌಲ್ಯ (₹)', 'hi' => 'दर्ज किराया मूल्य (₹)'],
            'Only a few reviews are available; read comments manually before drawing conclusions.' => ['kn' => 'ಕೆಲವೇ ವಿಮರ್ಶೆಗಳಿವೆ; ತೀರ್ಮಾನಿಸುವ ಮೊದಲು ಕಾಮೆಂಟ್‌ಗಳನ್ನು ಓದಿ.', 'hi' => 'केवल कुछ समीक्षाएँ हैं; निष्कर्ष निकालने से पहले टिप्पणियाँ पढ़ें।'],
            'Available' => ['kn' => 'ಲಭ್ಯವಿದೆ', 'hi' => 'उपलब्ध'],
            'Unavailable' => ['kn' => 'ಲಭ್ಯವಿಲ್ಲ', 'hi' => 'अनुपलब्ध'],
            'Active' => ['kn' => 'ಸಕ್ರಿಯ', 'hi' => 'सक्रिय'],
            'Inactive' => ['kn' => 'ನಿಷ್ಕ್ರಿಯ', 'hi' => 'निष्क्रिय'],
            'Good' => ['kn' => 'ಉತ್ತಮ', 'hi' => 'अच्छा'],
            'Fair' => ['kn' => 'ಸಾಧಾರಣ', 'hi' => 'ठीक'],
            'Poor' => ['kn' => 'ಕಳಪೆ', 'hi' => 'खराब'],
            'Maintain current price' => ['kn' => 'ಪ್ರಸ್ತುತ ಬೆಲೆಯನ್ನು ಮುಂದುವರಿಸಿ', 'hi' => 'वर्तमान कीमत बनाए रखें'],
            'Increase price' => ['kn' => 'ಬೆಲೆಯನ್ನು ಹೆಚ್ಚಿಸಿ', 'hi' => 'कीमत बढ़ाएँ'],
            'Reduce price' => ['kn' => 'ಬೆಲೆಯನ್ನು ಕಡಿಮೆ ಮಾಡಿ', 'hi' => 'कीमत घटाएँ'],
            'High' => ['kn' => 'ಹೆಚ್ಚು', 'hi' => 'उच्च'],
            'Medium' => ['kn' => 'ಮಧ್ಯಮ', 'hi' => 'मध्यम'],
            'Low' => ['kn' => 'ಕಡಿಮೆ', 'hi' => 'कम'],
            'days' => ['kn' => 'ದಿನಗಳು', 'hi' => 'दिन'],
            'No reviews yet' => ['kn' => 'ಇನ್ನೂ ವಿಮರ್ಶೆಗಳಿಲ್ಲ', 'hi' => 'अभी तक कोई समीक्षा नहीं'],
            'Smart Rental Pricing & Price History' => ['kn' => 'ಸ್ಮಾರ್ಟ್ ಬಾಡಿಗೆ ಬೆಲೆ ಮತ್ತು ಬೆಲೆ ಇತಿಹಾಸ', 'hi' => 'स्मार्ट किराया मूल्य और मूल्य इतिहास'],
            'Available' => ['kn' => 'ಲಭ್ಯವಿದೆ', 'hi' => 'उपलब्ध'],
            'Unavailable' => ['kn' => 'ಲಭ್ಯವಿಲ್ಲ', 'hi' => 'अनुपलब्ध'],
            'Good' => ['kn' => 'ಉತ್ತಮ', 'hi' => 'अच्छी'],
            'Fair' => ['kn' => 'ಸಾಧಾರಣ', 'hi' => 'ठीक-ठाक'],
            'Poor' => ['kn' => 'ಕಳಪೆ', 'hi' => 'खराब'],
            'Near category average' => ['kn' => 'ವರ್ಗದ ಸರಾಸರಿಗೆ ಸಮೀಪ', 'hi' => 'श्रेणी के औसत के करीब'],
            'Above category average' => ['kn' => 'ವರ್ಗದ ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚು', 'hi' => 'श्रेणी के औसत से अधिक'],
            'Below category average' => ['kn' => 'ವರ್ಗದ ಸರಾಸರಿಗಿಂತ ಕಡಿಮೆ', 'hi' => 'श्रेणी के औसत से कम'],
            'Maintain current price' => ['kn' => 'ಪ್ರಸ್ತುತ ಬೆಲೆಯನ್ನು ಮುಂದುವರಿಸಿ', 'hi' => 'वर्तमान मूल्य बनाए रखें'],
            'Maintain or consider a small reduction' => ['kn' => 'ಪ್ರಸ್ತುತ ಬೆಲೆ ಉಳಿಸಿ ಅಥವಾ ಸ್ವಲ್ಪ ಕಡಿಮೆ ಮಾಡುವುದನ್ನು ಪರಿಗಣಿಸಿ', 'hi' => 'वर्तमान मूल्य बनाए रखें या थोड़ी कमी पर विचार करें'],
            'Increase price cautiously' => ['kn' => 'ಬೆಲೆಯನ್ನು ಎಚ್ಚರಿಕೆಯಿಂದ ಹೆಚ್ಚಿಸಿ', 'hi' => 'सावधानी से मूल्य बढ़ाएँ'],
            'Low' => ['kn' => 'ಕಡಿಮೆ', 'hi' => 'कम'],
            'Moderate' => ['kn' => 'ಮಧ್ಯಮ', 'hi' => 'मध्यम'],
            'High' => ['kn' => 'ಹೆಚ್ಚು', 'hi' => 'अधिक'],
            'days' => ['kn' => 'ದಿನಗಳು', 'hi' => 'दिन'],
            'The available signals do not strongly support a price change. Keep the current rate and collect more booking and review data.' => ['kn' => 'ಲಭ್ಯವಿರುವ ಸೂಚನೆಗಳು ಬೆಲೆ ಬದಲಾವಣೆಯನ್ನು ಬಲವಾಗಿ ಬೆಂಬಲಿಸುವುದಿಲ್ಲ. ಪ್ರಸ್ತುತ ದರವನ್ನು ಉಳಿಸಿ, ಹೆಚ್ಚಿನ ಬುಕಿಂಗ್ ಮತ್ತು ವಿಮರ್ಶೆ ಮಾಹಿತಿಯನ್ನು ಸಂಗ್ರಹಿಸಿ.', 'hi' => 'उपलब्ध संकेत मूल्य बदलने का मजबूत समर्थन नहीं करते। वर्तमान दर बनाए रखें और अधिक बुकिंग व समीक्षा डेटा एकत्र करें।'],
            'Demand, condition, reviews, weather or price comparison raise caution. Improve the underlying issue first; if the price is above category average, test a reduction of about 5%.' => ['kn' => 'ಬೇಡಿಕೆ, ಸ್ಥಿತಿ, ವಿಮರ್ಶೆಗಳು, ಹವಾಮಾನ ಅಥವಾ ಬೆಲೆ ಹೋಲಿಕೆ ಎಚ್ಚರಿಕೆ ಸೂಚಿಸುತ್ತವೆ. ಮೊದಲು ಮೂಲ ಸಮಸ್ಯೆಯನ್ನು ಸುಧಾರಿಸಿ; ಬೆಲೆ ವರ್ಗದ ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚಿದ್ದರೆ ಸುಮಾರು 5% ಕಡಿತವನ್ನು ಪರೀಕ್ಷಿಸಿ.', 'hi' => 'मांग, स्थिति, समीक्षाएँ, मौसम या मूल्य तुलना सावधानी का संकेत देते हैं। पहले मूल समस्या सुधारें; मूल्य श्रेणी औसत से अधिक हो तो लगभग 5% कमी आज़माएँ।'],
            'The equipment is marked Good; maintain its condition with regular inspections.' => ['kn' => 'ಉಪಕರಣದ ಸ್ಥಿತಿ ಉತ್ತಮವೆಂದು ಗುರುತಿಸಲಾಗಿದೆ; ನಿಯಮಿತ ಪರಿಶೀಲನೆಗಳಿಂದ ಅದನ್ನು ಕಾಪಾಡಿ.', 'hi' => 'उपकरण की स्थिति अच्छी दर्ज है; नियमित निरीक्षण से इसे बनाए रखें।'],
            'No customer reviews are available yet; pricing confidence is limited.' => ['kn' => 'ಇನ್ನೂ ಗ್ರಾಹಕರ ವಿಮರ್ಶೆಗಳಿಲ್ಲ; ಆದ್ದರಿಂದ ಬೆಲೆ ಅಂದಾಜಿನ ವಿಶ್ವಾಸ ಸೀಮಿತವಾಗಿದೆ.', 'hi' => 'अभी ग्राहक समीक्षाएँ उपलब्ध नहीं हैं; इसलिए मूल्य अनुमान पर भरोसा सीमित है।'],
            'Recorded booking history is low (0 booking(s)); avoid assuming customers will accept a higher price.' => ['kn' => 'ದಾಖಲಾದ ಬುಕಿಂಗ್ ಇತಿಹಾಸ ಕಡಿಮೆಯಾಗಿದೆ (0 ಬುಕಿಂಗ್); ಗ್ರಾಹಕರು ಹೆಚ್ಚಿನ ಬೆಲೆಯನ್ನು ಒಪ್ಪಿಕೊಳ್ಳುತ್ತಾರೆಂದು ಊಹಿಸಬೇಡಿ.', 'hi' => 'दर्ज बुकिंग इतिहास कम है (0 बुकिंग); यह न मानें कि ग्राहक अधिक मूल्य स्वीकार करेंगे।'],
            'The next-two-day weather outlook may support demand for this equipment category.' => ['kn' => 'ಮುಂದಿನ ಎರಡು ದಿನಗಳ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ಈ ಉಪಕರಣ ವರ್ಗದ ಬೇಡಿಕೆಗೆ ಬೆಂಬಲ ನೀಡಬಹುದು.', 'hi' => 'अगले दो दिनों का मौसम पूर्वानुमान इस उपकरण श्रेणी की मांग को समर्थन दे सकता है।'],
            'The current price is close to the category average.' => ['kn' => 'ಪ್ರಸ್ತುತ ಬೆಲೆ ವರ್ಗದ ಸರಾಸರಿಗೆ ಸಮೀಪದಲ್ಲಿದೆ.', 'hi' => 'वर्तमान मूल्य श्रेणी के औसत के करीब है।'],
            'The forecast is relatively dry for many general tractor tasks.' => ['kn' => 'ಸಾಮಾನ್ಯ ಟ್ರಾಕ್ಟರ್ ಕೆಲಸಗಳಿಗೆ ಮುನ್ಸೂಚನೆಯಲ್ಲಿ ಮಳೆ ಕಡಿಮೆ ಇರುವ ಸಾಧ್ಯತೆ ಇದೆ.', 'hi' => 'कई सामान्य ट्रैक्टर कार्यों के लिए पूर्वानुमान अपेक्षाकृत शुष्क है।'],
            'The current weather forecast does not provide a strong demand signal for this category.' => ['kn' => 'ಪ್ರಸ್ತುತ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ಈ ವರ್ಗಕ್ಕೆ ಹೆಚ್ಚಿನ ಬೇಡಿಕೆಯ ಸ್ಪಷ್ಟ ಸೂಚನೆ ನೀಡುವುದಿಲ್ಲ.', 'hi' => 'वर्तमान मौसम पूर्वानुमान इस श्रेणी की मांग का मजबूत संकेत नहीं देता।'],
            'The current price is more than 10% above the category average; check local competitors before increasing it.' => ['kn' => 'ಪ್ರಸ್ತುತ ಬೆಲೆ ವರ್ಗದ ಸರಾಸರಿಗಿಂತ 10% ಕ್ಕಿಂತ ಹೆಚ್ಚು ಇದೆ; ಹೆಚ್ಚಿಸುವ ಮೊದಲು ಸ್ಥಳೀಯ ಸ್ಪರ್ಧಿಗಳ ಬೆಲೆ ಪರಿಶೀಲಿಸಿ.', 'hi' => 'वर्तमान मूल्य श्रेणी के औसत से 10% से अधिक है; बढ़ाने से पहले स्थानीय प्रतिस्पर्धियों की कीमत जाँचें।'],
        ];
        return isset($translations[$text][$lang]) ? $translations[$text][$lang] : $text;
    }
}
?>

<!doctype html><html lang="<?= h($current_lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h(lai_page_t("Equipment AI Recommendations")) ?> - <?= h(lai_page_t("Agriculture Equipment Rental System")) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f4;color:#26352d;font-family:'Segoe UI',Arial,sans-serif}.main-content{margin-left:250px;padding:28px;min-height:100vh}.hero{padding:26px;margin-bottom:22px;border-radius:18px;color:white;background:linear-gradient(120deg,#147d50,#248f9b,#5366c8)}.hero h1{font-weight:750;font-size:clamp(24px,3vw,34px);margin:10px 0}.hero p{margin:0}.pill{display:inline-block;padding:5px 10px;border-radius:30px;background:#e2f5e9;color:#17683b;font-size:12px;font-weight:700}.panel,.summary-card,.feature-card,.equipment-card{background:white;border:1px solid #e5ece7;border-radius:16px;box-shadow:0 6px 20px #2e47400d}.panel{padding:22px;margin-bottom:20px}.panel h2{font-size:20px;font-weight:750;margin:0 0 16px}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.summary-card{padding:18px}.summary-card small,.muted{color:#66756b}.summary-value{display:block;margin-top:7px;font-size:24px;font-weight:750;overflow-wrap:anywhere}.feature-grid,.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.feature-card{padding:20px;color:inherit;text-decoration:none;display:block}.feature-icon{font-size:24px;color:#168050;margin-bottom:12px}.equipment-card{padding:18px;min-width:0}.metric-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e8eee9}.metric-row strong{text-align:right;overflow-wrap:anywhere}.muted{font-size:13px}.notice{background:#fff8df;border:1px solid #f1dfa0;border-radius:12px;padding:14px 16px;margin-bottom:18px;color:#69551a}.chart-container{position:relative;height:300px}.table-responsive{overflow-x:auto}@media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.feature-grid,.equipment-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{margin-left:0;padding:15px}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.summary-card{padding:13px}.summary-value{font-size:20px}.panel,.hero{padding:17px}}
.equipment-detail-card{padding:0;overflow:hidden}.equipment-summary{list-style:none;cursor:pointer;padding:18px;display:flex;flex-direction:column;gap:9px}.equipment-summary::-webkit-details-marker{display:none}.equipment-summary:after{content:"＋";align-self:flex-end;font-size:20px;color:#168050;font-weight:700}.equipment-detail-card details[open]>.equipment-summary:after{content:"−"}.equipment-name{font-size:20px;font-weight:700;color:#26352d}.equipment-details{padding:0 18px 18px}.equipment-summary:hover{background:#f7fbf8}</style></head><body><?php include __DIR__ . '/lender_sidebar.php'; ?><main class="main-content"><section class="hero"><span class="pill"><?= lai_page_t('AI-ASSISTED ANALYSIS') ?></span><h1><?= lai_page_t('Equipment AI Recommendations') ?></h1><p><?= lai_page_t('Review demand signals, weather outlook and explainable pricing suggestions.') ?></p></section><div class="mb-3"><a class="btn btn-outline-success" href="lender_ai_insights.php"><i class="fa-solid fa-arrow-left me-2"></i><?= lai_page_t('Back to AI Insights Dashboard') ?></a></div>
<div class="d-flex justify-content-end align-items-center gap-2 mb-3">

<select id="aiPageLanguage" class="form-select form-select-sm" style="width:auto;min-width:145px" aria-label="Select language">
<option value="en" <?= $current_lang === 'en' ? 'selected' : '' ?>>English</option>
<option value="kn" <?= $current_lang === 'kn' ? 'selected' : '' ?>>ಕನ್ನಡ (Kannada)</option>
<option value="hi" <?= $current_lang === 'hi' ? 'selected' : '' ?>>हिन्दी (Hindi)</option>
</select></div>
<script>
document.getElementById('aiPageLanguage')?.addEventListener('change', function () {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', this.value);
    window.location.href = url.toString();
});
</script>
<section class="panel">
        <h2><?= lai_page_t('Weather-Based Demand Outlook (Next 2 Days)') ?></h2>
        <p class="muted"><?= lai_page_t('Location is taken automatically from your registered lender profile. This is a rule-based weather impact estimate, not a trained ML prediction or a guarantee of bookings.') ?></p>
        <?php if (!$weatherOutlook['available']): ?>
            <div class="notice mb-0"><?= h($weatherOutlook['message']) ?></div>
        <?php else: ?>
            <div class="summary-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                <div class="summary-card">
                    <small><?= lai_page_t('Forecast location') ?></small>
                    <span class="summary-value" style="font-size:19px;"><?= h($weatherOutlook['location']) ?></span>
                </div>
                <div class="summary-card">
                    <small><?= lai_page_t('Average rain chance') ?></small>
                    <span class="summary-value"><?= $weatherOutlook['rain_probability'] === null ? 'N/A' : (int)$weatherOutlook['rain_probability'] . '%' ?></span>
                </div>
                <div class="summary-card">
                    <small><?= lai_page_t('Total forecast rainfall') ?></small>
                    <span class="summary-value"><?= $weatherOutlook['precipitation'] === null ? 'N/A' : number_format((float)$weatherOutlook['precipitation'], 1) . ' mm' ?></span>
                </div>
            </div>
            <div class="equipment-grid">
                <?php foreach ($weatherOutlook['days'] as $forecastDay): ?>
                    <article class="equipment-card">
                        <h3><?= h(date('D, d M', strtotime($forecastDay['date']))) ?></h3>
                        <div class="metric-row"><span><?= lai_page_t('Rain probability') ?></span><strong><?= $forecastDay['rain_probability'] === null ? 'N/A' : (int)$forecastDay['rain_probability'] . '%' ?></strong></div>
                        <div class="metric-row"><span><?= lai_page_t('Expected rainfall') ?></span><strong><?= $forecastDay['precipitation'] === null ? 'N/A' : number_format((float)$forecastDay['precipitation'], 1) . ' mm' ?></strong></div>
                        <div class="metric-row"><span><?= lai_page_t('Temperature') ?></span><strong><?= $forecastDay['temperature_min'] === null || $forecastDay['temperature_max'] === null ? 'N/A' : round($forecastDay['temperature_min']) . '°C – ' . round($forecastDay['temperature_max']) . '°C' ?></strong></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="muted mt-3 mb-0"><?= lai_page_t('Weather source: Open-Meteo. Forecasts may change; check local field conditions before making rental decisions.') ?></p>
        <?php endif; ?>
    </section><section class="panel">
        <h2><?= lai_page_t('Smart Rental Pricing & Price History') ?></h2>
        <p class="muted"><?= lai_page_t('Suggested prices use the current average daily price of active equipment in the same category. Price history starts from the setup date\'s baseline and records future price changes automatically.') ?></p>
        <?php if (empty($priceHistory)): ?>
            <div class="notice mb-0"><?= lai_page_t('No price history is available yet. Check that the Step 3 SQL setup completed successfully and that equipment exists for your lender account.') ?></div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="priceHistoryChart"></canvas>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr><th><?= lai_page_t('Equipment') ?></th><th><?= lai_page_t('Change type') ?></th><th><?= lai_page_t('Previous price') ?></th><th><?= lai_page_t('New price') ?></th><th><?= lai_page_t('Date recorded') ?></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($priceHistory) as $historyRow): ?>
                        <tr>
                            <td><?= h($historyRow['title']) ?></td>
                            <td><?= h($historyRow['change_type']) ?></td>
                            <td><?= $historyRow['old_price'] === null ? '—' : money($historyRow['old_price']) ?></td>
                            <td><?= money($historyRow['new_price']) ?></td>
                            <td><?= h(date('d M Y, h:i A', strtotime($historyRow['changed_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="muted mb-0"><?= lai_page_t('Pricing suggestions are reference estimates, not guaranteed market prices. Compare equipment condition, local demand and service costs before changing a rate.') ?></p>
    </section><section class="panel">
        <h2><?= lai_page_t('Equipment Utilization Comparison') ?></h2>

        <?php if (empty($equipmentInsights)): ?>
            <p><?= lai_page_t('No equipment has been added to your account yet.') ?></p>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="utilizationChart"></canvas>
            </div>
            <p class="muted mt-3">
                Estimated from recorded rental days over a 90-day period.
                This is an approximate indicator, not verified machine operating time.
            </p>
        <?php endif; ?>
    </section>
<section class="panel">
        <h2><?= lai_page_t('Equipment AI Recommendations') ?></h2>

        <p>
            <strong><?= lai_page_t('Overall summary:') ?></strong>
            <?= h($overallDemand) ?>.
            Review each equipment item below before changing prices or scheduling maintenance.
        </p>

        <?php if (empty($equipmentInsights)): ?>
            <p><?= lai_page_t('No recommendations are available until you add equipment.') ?></p>
        <?php else: ?>

            <div class="equipment-grid">

                <?php foreach ($equipmentInsights as $item): ?>

                    <article class="equipment-card equipment-detail-card">
                        <details>
                            <summary class="equipment-summary">
                                <span class="pill"><?= h($item['category']) ?></span>
                                <span class="equipment-name"><?= h($item['title']) ?></span>
                                <span class="muted"><?= lai_page_t('Click to view AI recommendation details') ?></span>
                            </summary>
                            <div class="equipment-details">

                        <div class="metric-row">
                            <span><?= lai_page_t('Equipment status') ?></span>
                            <strong><?= h(lai_page_t((string)$item['status'])) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Recorded bookings') ?></span>
                            <strong><?= (int)$item['bookings'] ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Estimated accumulated rental-days') ?></span>
                            <strong><?= (int)$item['rental_days'] ?> <?= lai_page_t('days') ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Completed / returned rentals') ?></span>
                            <strong><?= (int)$item['completed_rentals'] ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Estimated demand from history') ?></span>
                            <strong><?= h(lai_page_t((string)$item['demand'])) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Weather-based outlook') ?></span>
                            <strong><?= h(lai_page_t((string)$item['weather_demand'])) ?></strong>
                        </div>
                        <p class="muted mt-2"><?= h(lai_page_t((string)$item['weather_reason'])) ?></p>

                        <div class="metric-row">
                            <span><?= lai_page_t('Equipment condition') ?></span>
                            <strong><?= h(lai_page_t((string)$item['condition'])) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Customer reviews') ?></span>
                            <strong><?= $item['average_rating'] === null ? lai_page_t('No reviews yet') : number_format((float)$item['average_rating'], 1) . '/5 (' . (int)$item['review_count'] . ')' ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Current daily price') ?></span>
                            <strong><?= money($item['price']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Category average price') ?></span>
                            <strong><?= money($item['average']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Price position') ?></span>
                            <strong><?= h(lai_page_t(ucfirst((string)$item['price_position']))) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('AI pricing action') ?></span>
                            <strong><?= h(lai_page_t((string)$item['price_action'])) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Suggested action price') ?></span>
                            <strong><?= money($item['action_price']) ?></strong>
                        </div>

                        <p class="muted mt-2"><?= h(lai_page_t((string)$item['action_detail'])) ?></p>
                        <strong><?= lai_page_t('Why this recommendation?') ?></strong>
                        <ul class="mt-2">
                            <?php foreach ($item['pricing_reasons'] as $reason): ?>
                                <li><?= h(lai_page_t((string)$reason)) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="metric-row">
                            <span><?= lai_page_t('Category-based reference price') ?></span>
                            <strong><?= money($item['suggested']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span><?= lai_page_t('Estimated utilization') ?></span>
                            <strong><?= (int)$item['utilization'] ?>%</strong>
                        </div>

                        
                            </div>
                        </details>
                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section><script><?php if (!empty($priceHistory)): ?>const labels=<?= json_encode($priceChartLabels,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;const datasets=<?= json_encode($priceChartDatasets,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;new Chart(document.getElementById('priceHistoryChart'),{type:'line',data:{labels:labels,datasets:datasets},options:{responsive:true,maintainAspectRatio:false,scales:{y:{ticks:{callback:v=>'₹'+v}}},plugins:{legend:{position:'bottom'}}}});<?php endif; ?><?php if (!empty($equipmentInsights)): ?>new Chart(document.getElementById('utilizationChart'),{type:'bar',data:{labels:<?= json_encode(array_column($equipmentInsights,'title')) ?>,datasets:[{label:'<?= h(lai_page_t('Estimated utilization (%)')) ?>',data:<?= json_encode(array_column($equipmentInsights,'utilization')) ?>,backgroundColor:'rgba(79,105,210,.72)'}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',scales:{x:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}}}}});<?php endif; ?></script></main></body></html>