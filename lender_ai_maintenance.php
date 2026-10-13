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
            'Only a few reviews are available; read comments manually before drawing conclusions.' => ['kn' => 'ಕೆಲವೇ ವಿಮರ್ಶೆಗಳಿವೆ; ತೀರ್ಮಾನಿಸುವ ಮೊದಲು ಕಾಮೆಂಟ್‌ಗಳನ್ನು ಓದಿ.', 'hi' => 'केवल कुछ समीक्षाएँ हैं; निष्कर्ष निकालने से पहले टिप्पणियाँ पढ़ें।']
        ];
        return isset($translations[$text][$lang]) ? $translations[$text][$lang] : $text;
    }
}
?>

<!doctype html><html lang="<?= h($current_lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h(lai_page_t("Maintenance Guidance")) ?> - <?= h(lai_page_t("Agriculture Equipment Rental System")) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f4;color:#26352d;font-family:'Segoe UI',Arial,sans-serif}.main-content{margin-left:250px;padding:28px;min-height:100vh}.hero{padding:26px;margin-bottom:22px;border-radius:18px;color:white;background:linear-gradient(120deg,#147d50,#248f9b,#5366c8)}.hero h1{font-weight:750;font-size:clamp(24px,3vw,34px);margin:10px 0}.hero p{margin:0}.pill{display:inline-block;padding:5px 10px;border-radius:30px;background:#e2f5e9;color:#17683b;font-size:12px;font-weight:700}.panel,.summary-card,.feature-card,.equipment-card{background:white;border:1px solid #e5ece7;border-radius:16px;box-shadow:0 6px 20px #2e47400d}.panel{padding:22px;margin-bottom:20px}.panel h2{font-size:20px;font-weight:750;margin:0 0 16px}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.summary-card{padding:18px}.summary-card small,.muted{color:#66756b}.summary-value{display:block;margin-top:7px;font-size:24px;font-weight:750;overflow-wrap:anywhere}.feature-grid,.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.feature-card{padding:20px;color:inherit;text-decoration:none;display:block}.feature-icon{font-size:24px;color:#168050;margin-bottom:12px}.equipment-card{padding:18px;min-width:0}.metric-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e8eee9}.metric-row strong{text-align:right;overflow-wrap:anywhere}.muted{font-size:13px}.notice{background:#fff8df;border:1px solid #f1dfa0;border-radius:12px;padding:14px 16px;margin-bottom:18px;color:#69551a}.chart-container{position:relative;height:300px}.table-responsive{overflow-x:auto}@media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.feature-grid,.equipment-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{margin-left:0;padding:15px}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.summary-card{padding:13px}.summary-value{font-size:20px}.panel,.hero{padding:17px}}
</style></head><body><?php include __DIR__ . '/lender_sidebar.php'; ?><main class="main-content"><section class="hero"><span class="pill"><?= lai_page_t('AI-ASSISTED ANALYSIS') ?></span><h1><?= lai_page_t('Maintenance Guidance') ?></h1><p><?= lai_page_t('Use rental activity as a reminder to inspect equipment and plan routine care.') ?></p></section><div class="mb-3"><a class="btn btn-outline-success" href="lender_ai_insights.php"><i class="fa-solid fa-arrow-left me-2"></i><?= lai_page_t('Back to AI Insights Dashboard') ?></a></div>
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
<div class="notice"><strong><?= lai_page_t('Important:') ?></strong> <?= lai_page_t('Usage thresholds are illustrative project rules, not manufacturer service intervals. Use operating-hour records and official service schedules when available.') ?></div><section class="panel"><h2><?= lai_page_t('Equipment Maintenance Guidance') ?></h2><?php if(empty($equipmentInsights)): ?><p><?= lai_page_t('No equipment has been added yet.') ?></p><?php else: ?><div class="equipment-grid"><?php foreach($equipmentInsights as $item): ?><article class="equipment-card"><span class="pill"><?= h($item['category']) ?></span><h3 class="mt-3"><?= h($item['title']) ?></h3><div class="metric-row"><span><?= lai_page_t('Status') ?></span><strong><?= h($item['status']) ?></strong></div><div class="metric-row"><span><?= lai_page_t('Estimated rental-days') ?></span><strong><?= (int)$item['rental_days'] ?></strong></div><div class="metric-row"><span><?= lai_page_t('Completed / returned rentals') ?></span><strong><?= (int)$item['completed_rentals'] ?></strong></div><div class="metric-row"><span><?= lai_page_t('Condition') ?></span><strong><?= h($item['condition']) ?></strong></div><p class="mt-3 mb-0"><?= h($item['maintenance']) ?></p></article><?php endforeach; ?></div><?php endif; ?></section><section class="panel"><h2><?= lai_page_t('General inspection reminders') ?></h2><ul class="mb-0"><li><?= lai_page_t('Check visible damage, tyres, brakes, belts and guards as applicable.') ?></li><li><?= lai_page_t('Check fluids and leaks for the equipment type.') ?></li><li><?= lai_page_t('Record faults, repairs and service dates after returns.') ?></li><li><?= lai_page_t('Follow manufacturer guidance and operating-hour intervals.') ?></li><li><?= lai_page_t('Resolve under-maintenance status before renting the equipment again.') ?></li></ul></section><p class="muted"><?= lai_page_t('Rental-days are estimated from booking records, not verified machine operating time.') ?></p></main></body></html>