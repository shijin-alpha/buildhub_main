import React, { useState, useEffect } from 'react';
import { useNotifications } from '../hooks/useNotifications';
import NotificationToast from './NotificationToast';
import '../styles/EnhancedRequestForm.css';

// District mapping for Indian states
const stateDistricts = {
  'Kerala': ['Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad'],
  'Tamil Nadu': ['Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore', 'Dharmapuri', 'Dindigul', 'Erode', 'Kallakurichi', 'Kanchipuram', 'Kanyakumari', 'Karur', 'Krishnagiri', 'Madurai', 'Mayiladuthurai', 'Nagapattinam', 'Namakkal', 'Nilgiris', 'Perambalur', 'Pudukkottai', 'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga', 'Tenkasi', 'Thanjavur', 'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli', 'Tirupathur', 'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur', 'Vellore', 'Viluppuram', 'Virudhunagar'],
  'Karnataka': ['Bagalkot', 'Ballari', 'Belagavi', 'Bengaluru Rural', 'Bengaluru Urban', 'Bidar', 'Chamarajanagar', 'Chikkaballapur', 'Chikkamagaluru', 'Chitradurga', 'Dakshina Kannada', 'Davanagere', 'Dharwad', 'Gadag', 'Hassan', 'Haveri', 'Kalaburagi', 'Kodagu', 'Kolar', 'Koppal', 'Mandya', 'Mysuru', 'Raichur', 'Ramanagara', 'Shivamogga', 'Tumakuru', 'Udupi', 'Uttara Kannada', 'Vijayapura', 'Yadgir'],
  'Andhra Pradesh': ['Anantapur', 'Chittoor', 'East Godavari', 'Guntur', 'Krishna', 'Kurnool', 'Nellore', 'Prakasam', 'Srikakulam', 'Visakhapatnam', 'Vizianagaram', 'West Godavari', 'YSR Kadapa'],
  'Telangana': ['Adilabad', 'Bhadradri Kothagudem', 'Hyderabad', 'Jagtial', 'Jangaon', 'Jayashankar', 'Jogulamba', 'Kamareddy', 'Karimnagar', 'Khammam', 'Komaram Bheem', 'Mahabubabad', 'Mahbubnagar', 'Mancherial', 'Medak', 'Medchal', 'Nagarkurnool', 'Nalgonda', 'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla', 'Rangareddy', 'Sangareddy', 'Siddipet', 'Suryapet', 'Vikarabad', 'Wanaparthy', 'Warangal Rural', 'Warangal Urban', 'Yadadri Bhuvanagiri'],
  'Maharashtra': ['Ahmednagar', 'Akola', 'Amravati', 'Aurangabad', 'Beed', 'Bhandara', 'Buldhana', 'Chandrapur', 'Dhule', 'Gadchiroli', 'Gondia', 'Hingoli', 'Jalgaon', 'Jalna', 'Kolhapur', 'Latur', 'Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Nanded', 'Nandurbar', 'Nashik', 'Osmanabad', 'Palghar', 'Parbhani', 'Pune', 'Raigad', 'Ratnagiri', 'Sangli', 'Satara', 'Sindhudurg', 'Solapur', 'Thane', 'Wardha', 'Washim', 'Yavatmal'],
  'Gujarat': ['Ahmedabad', 'Amreli', 'Anand', 'Aravalli', 'Banaskantha', 'Bharuch', 'Bhavnagar', 'Botad', 'Chhota Udaipur', 'Dahod', 'Dang', 'Devbhoomi Dwarka', 'Gandhinagar', 'Gir Somnath', 'Jamnagar', 'Junagadh', 'Kheda', 'Kutch', 'Mahisagar', 'Mehsana', 'Morbi', 'Narmada', 'Navsari', 'Panchmahal', 'Patan', 'Porbandar', 'Rajkot', 'Sabarkantha', 'Surat', 'Surendranagar', 'Tapi', 'Vadodara', 'Valsad'],
  'Rajasthan': ['Ajmer', 'Alwar', 'Banswara', 'Baran', 'Barmer', 'Bharatpur', 'Bhilwara', 'Bikaner', 'Bundi', 'Chittorgarh', 'Churu', 'Dausa', 'Dholpur', 'Dungarpur', 'Hanumangarh', 'Jaipur', 'Jaisalmer', 'Jalore', 'Jhalawar', 'Jhunjhunu', 'Jodhpur', 'Karauli', 'Kota', 'Nagaur', 'Pali', 'Pratapgarh', 'Rajsamand', 'Sawai Madhopur', 'Sikar', 'Sirohi', 'Sri Ganganagar', 'Tonk', 'Udaipur'],
  'Uttar Pradesh': ['Agra', 'Aligarh', 'Ambedkar Nagar', 'Amethi', 'Amroha', 'Auraiya', 'Ayodhya', 'Azamgarh', 'Baghpat', 'Bahraich', 'Ballia', 'Balrampur', 'Banda', 'Barabanki', 'Bareilly', 'Basti', 'Bhadohi', 'Bijnor', 'Budaun', 'Bulandshahr', 'Chandauli', 'Chitrakoot', 'Deoria', 'Etah', 'Etawah', 'Farrukhabad', 'Fatehpur', 'Firozabad', 'Gautam Buddha Nagar', 'Ghaziabad', 'Ghazipur', 'Gonda', 'Gorakhpur', 'Hamirpur', 'Hapur', 'Hardoi', 'Hathras', 'Jalaun', 'Jaunpur', 'Jhansi', 'Kannauj', 'Kanpur Dehat', 'Kanpur Nagar', 'Kasganj', 'Kaushambi', 'Kheri', 'Kushinagar', 'Lalitpur', 'Lucknow', 'Maharajganj', 'Mahoba', 'Mainpuri', 'Mathura', 'Mau', 'Meerut', 'Mirzapur', 'Moradabad', 'Muzaffarnagar', 'Pilibhit', 'Pratapgarh', 'Prayagraj', 'Raebareli', 'Rampur', 'Saharanpur', 'Sambhal', 'Sant Kabir Nagar', 'Shahjahanpur', 'Shamli', 'Shravasti', 'Siddharthnagar', 'Sitapur', 'Sonbhadra', 'Sultanpur', 'Unnao', 'Varanasi'],
  'West Bengal': ['Alipurduar', 'Bankura', 'Birbhum', 'Cooch Behar', 'Dakshin Dinajpur', 'Darjeeling', 'Hooghly', 'Howrah', 'Jalpaiguri', 'Jhargram', 'Kalimpong', 'Kolkata', 'Malda', 'Murshidabad', 'Nadia', 'North 24 Parganas', 'Paschim Bardhaman', 'Paschim Medinipur', 'Purba Bardhaman', 'Purba Medinipur', 'Purulia', 'South 24 Parganas', 'Uttar Dinajpur'],
  'Madhya Pradesh': ['Agar Malwa', 'Alirajpur', 'Anuppur', 'Ashoknagar', 'Balaghat', 'Barwani', 'Betul', 'Bhind', 'Bhopal', 'Burhanpur', 'Chhatarpur', 'Chhindwara', 'Damoh', 'Datia', 'Dewas', 'Dhar', 'Dindori', 'Guna', 'Gwalior', 'Harda', 'Hoshangabad', 'Indore', 'Jabalpur', 'Jhabua', 'Katni', 'Khandwa', 'Khargone', 'Mandla', 'Mandsaur', 'Morena', 'Narsinghpur', 'Neemuch', 'Niwari', 'Panna', 'Raisen', 'Rajgarh', 'Ratlam', 'Rewa', 'Sagar', 'Satna', 'Sehore', 'Seoni', 'Shahdol', 'Shajapur', 'Sheopur', 'Shivpuri', 'Sidhi', 'Singrauli', 'Tikamgarh', 'Ujjain', 'Umaria', 'Vidisha'],
  'Bihar': ['Araria', 'Arwal', 'Aurangabad', 'Banka', 'Begusarai', 'Bhagalpur', 'Bhojpur', 'Buxar', 'Darbhanga', 'East Champaran', 'Gaya', 'Gopalganj', 'Jamui', 'Jehanabad', 'Kaimur', 'Katihar', 'Khagaria', 'Kishanganj', 'Lakhisarai', 'Madhepura', 'Madhubani', 'Munger', 'Muzaffarpur', 'Nalanda', 'Nawada', 'Patna', 'Purnia', 'Rohtas', 'Saharsa', 'Samastipur', 'Saran', 'Sheikhpura', 'Sheohar', 'Sitamarhi', 'Siwan', 'Supaul', 'Vaishali', 'West Champaran'],
  'Odisha': ['Angul', 'Balangir', 'Balasore', 'Bargarh', 'Bhadrak', 'Boudh', 'Cuttack', 'Deogarh', 'Dhenkanal', 'Gajapati', 'Ganjam', 'Jagatsinghpur', 'Jajpur', 'Jharsuguda', 'Kalahandi', 'Kandhamal', 'Kendrapara', 'Kendujhar', 'Khordha', 'Koraput', 'Malkangiri', 'Mayurbhanj', 'Nabarangpur', 'Nayagarh', 'Nuapada', 'Puri', 'Rayagada', 'Sambalpur', 'Subarnapur', 'Sundargarh'],
  'Jharkhand': ['Bokaro', 'Chatra', 'Deoghar', 'Dhanbad', 'Dumka', 'East Singhbhum', 'Garhwa', 'Giridih', 'Godda', 'Gumla', 'Hazaribagh', 'Jamtara', 'Khunti', 'Koderma', 'Latehar', 'Lohardaga', 'Pakur', 'Palamu', 'Ramgarh', 'Ranchi', 'Sahebganj', 'Seraikela Kharsawan', 'Simdega', 'West Singhbhum'],
  'Chhattisgarh': ['Balod', 'Baloda Bazar', 'Balrampur', 'Bastar', 'Bemetara', 'Bijapur', 'Bilaspur', 'Dantewada', 'Dhamtari', 'Durg', 'Gariaband', 'Janjgir-Champa', 'Jashpur', 'Kabirdham', 'Kanker', 'Kondagaon', 'Korba', 'Koriya', 'Mahasamund', 'Mungeli', 'Narayanpur', 'Raigarh', 'Raipur', 'Rajnandgaon', 'Sukma', 'Surajpur', 'Surguja'],
  'Punjab': ['Amritsar', 'Barnala', 'Bathinda', 'Faridkot', 'Fatehgarh Sahib', 'Fazilka', 'Ferozepur', 'Gurdaspur', 'Hoshiarpur', 'Jalandhar', 'Kapurthala', 'Ludhiana', 'Mansa', 'Moga', 'Mohali', 'Muktsar', 'Pathankot', 'Patiala', 'Rupnagar', 'Sangrur', 'Shaheed Bhagat Singh Nagar', 'Tarn Taran'],
  'Haryana': ['Ambala', 'Bhiwani', 'Charkhi Dadri', 'Faridabad', 'Fatehabad', 'Gurugram', 'Hisar', 'Jhajjar', 'Jind', 'Kaithal', 'Karnal', 'Kurukshetra', 'Mahendragarh', 'Nuh', 'Palwal', 'Panchkula', 'Panipat', 'Rewari', 'Rohtak', 'Sirsa', 'Sonipat', 'Yamunanagar'],
  'Himachal Pradesh': ['Bilaspur', 'Chamba', 'Hamirpur', 'Kangra', 'Kinnaur', 'Kullu', 'Lahaul and Spiti', 'Mandi', 'Shimla', 'Sirmaur', 'Solan', 'Una'],
  'Uttarakhand': ['Almora', 'Bageshwar', 'Chamoli', 'Champawat', 'Dehradun', 'Haridwar', 'Nainital', 'Pauri Garhwal', 'Pithoragarh', 'Rudraprayag', 'Tehri Garhwal', 'Udham Singh Nagar', 'Uttarkashi'],
  'Assam': ['Baksa', 'Barpeta', 'Biswanath', 'Bongaigaon', 'Cachar', 'Charaideo', 'Chirang', 'Darrang', 'Dhemaji', 'Dhubri', 'Dibrugarh', 'Dima Hasao', 'Goalpara', 'Golaghat', 'Hailakandi', 'Hojai', 'Jorhat', 'Kamrup', 'Kamrup Metropolitan', 'Karbi Anglong', 'Karimganj', 'Kokrajhar', 'Lakhimpur', 'Majuli', 'Morigaon', 'Nagaon', 'Nalbari', 'Sivasagar', 'Sonitpur', 'South Salmara-Mankachar', 'Tinsukia', 'Udalguri', 'West Karbi Anglong'],
  'Goa': ['North Goa', 'South Goa'],
  'Delhi': ['Central Delhi', 'East Delhi', 'New Delhi', 'North Delhi', 'North East Delhi', 'North West Delhi', 'Shahdara', 'South Delhi', 'South East Delhi', 'South West Delhi', 'West Delhi'],
  'Puducherry': ['Karaikal', 'Mahe', 'Puducherry', 'Yanam'],
  'Jammu and Kashmir': ['Anantnag', 'Bandipora', 'Baramulla', 'Budgam', 'Doda', 'Ganderbal', 'Jammu', 'Kathua', 'Kishtwar', 'Kulgam', 'Kupwara', 'Poonch', 'Pulwama', 'Rajouri', 'Ramban', 'Reasi', 'Samba', 'Shopian', 'Srinagar', 'Udhampur'],
  'Ladakh': ['Kargil', 'Leh'],
  'Chandigarh': ['Chandigarh'],
  'Arunachal Pradesh': ['Anjaw', 'Changlang', 'Dibang Valley', 'East Kameng', 'East Siang', 'Kamle', 'Kra Daadi', 'Kurung Kumey', 'Lepa Rada', 'Lohit', 'Longding', 'Lower Dibang Valley', 'Lower Siang', 'Lower Subansiri', 'Namsai', 'Pakke Kessang', 'Papum Pare', 'Shi Yomi', 'Siang', 'Tawang', 'Tirap', 'Upper Siang', 'Upper Subansiri', 'West Kameng', 'West Siang'],
  'Manipur': ['Bishnupur', 'Chandel', 'Churachandpur', 'Imphal East', 'Imphal West', 'Jiribam', 'Kakching', 'Kamjong', 'Kangpokpi', 'Noney', 'Pherzawl', 'Senapati', 'Tamenglong', 'Tengnoupal', 'Thoubal', 'Ukhrul'],
  'Meghalaya': ['East Garo Hills', 'East Jaintia Hills', 'East Khasi Hills', 'North Garo Hills', 'Ri Bhoi', 'South Garo Hills', 'South West Garo Hills', 'South West Khasi Hills', 'West Garo Hills', 'West Jaintia Hills', 'West Khasi Hills'],
  'Mizoram': ['Aizawl', 'Champhai', 'Hnahthial', 'Khawzawl', 'Kolasib', 'Lawngtlai', 'Lunglei', 'Mamit', 'Saiha', 'Saitual', 'Serchhip'],
  'Nagaland': ['Dimapur', 'Kiphire', 'Kohima', 'Longleng', 'Mokokchung', 'Mon', 'Noklak', 'Peren', 'Phek', 'Tuensang', 'Wokha', 'Zunheboto'],
  'Sikkim': ['East Sikkim', 'North Sikkim', 'South Sikkim', 'West Sikkim'],
  'Tripura': ['Dhalai', 'Gomati', 'Khowai', 'North Tripura', 'Sepahijala', 'South Tripura', 'Unakoti', 'West Tripura'],
  'Andaman and Nicobar Islands': ['Nicobar', 'North and Middle Andaman', 'South Andaman'],
  'Lakshadweep': ['Lakshadweep'],
  'Dadra and Nagar Haveli and Daman and Diu': ['Dadra and Nagar Haveli', 'Daman', 'Diu']
};

// Kerala Panchayats and Municipalities by District
const keralaPanchayatsMunicipalities = {
  'Thiruvananthapuram': {
    'Municipalities': ['Thiruvananthapuram Corporation', 'Attingal', 'Nedumangad', 'Varkala'],
    'Panchayats': ['Aruvikkara', 'Kallara', 'Karakulam', 'Karode', 'Kattakada', 'Manickal', 'Mangalapuram', 'Nagaroor', 'Pallichal', 'Parassala', 'Peringammala', 'Pullampara', 'Thirupuram', 'Uzhamalackal', 'Vellanad', 'Venganoor', 'Vilavoorkkal']
  },
  'Kollam': {
    'Municipalities': ['Kollam Corporation', 'Karunagappally', 'Kottarakkara', 'Paravur', 'Punalur'],
    'Panchayats': ['Adichanalloor', 'Alayamon', 'Chavara', 'Chithara', 'Clappana', 'Elamadu', 'Ittiva', 'Kareepra', 'Karunagappally', 'Kulakkada', 'Kulathupuzha', 'Mayyanad', 'Nedumpana', 'Neendakara', 'Oachira', 'Panmana', 'Pathanapuram', 'Perayam', 'Perinad', 'Poothakkulam', 'Sasthamcotta', 'Thazhava', 'Thekkumbhagam', 'Thevalakkara', 'Thrikkadavoor', 'Veliyam', 'Vilakkudy', 'West Kallada']
  },
  'Pathanamthitta': {
    'Municipalities': ['Pathanamthitta', 'Adoor', 'Pandalam', 'Thiruvalla'],
    'Panchayats': ['Aranmula', 'Cherukole', 'Chittar', 'Enadimangalam', 'Eraviperoor', 'Ezhamkulam', 'Kadapra', 'Kalanjoor', 'Kallooppara', 'Kaviyoor', 'Kodumon', 'Konni', 'Kozhencherry', 'Kumbazha', 'Kunnamthanam', 'Mallappally', 'Malayalappuzha', 'Mezhuveli', 'Mylapra', 'Naranganam', 'Nariyapuram', 'Niranam', 'Omalloor', 'Pallickal', 'Peringara', 'Pramadom', 'Puramattom', 'Ranni', 'Seethathode', 'Thottappuzhassery', 'Vadasserikara', 'Vechoochira']
  },
  'Alappuzha': {
    'Municipalities': ['Alappuzha', 'Chengannur', 'Cherthala', 'Kayamkulam', 'Mavelikkara'],
    'Panchayats': ['Ala', 'Ambalappuzha North', 'Ambalappuzha South', 'Arattupuzha', 'Arookutty', 'Aroor', 'Budhanoor', 'Champakulam', 'Chennam Pallippuram', 'Cheppad', 'Cheriyanad', 'Chingoli', 'Devikulangara', 'Edathua', 'Ezhupunna', 'Harippad', 'Kadakkarappally', 'Kainakary', 'Kanjikuzhy', 'Karthikappally', 'Kavalam', 'Kodamthurath', 'Krishnapuram', 'Kumarapuram', 'Kuthiyathode', 'Mannancherry', 'Mararikulam North', 'Mararikulam South', 'Muttar', 'Neelamperoor', 'Nedumudy', 'Panavally', 'Pandanad', 'Pattanakkad', 'Pulincunnu', 'Puliyoor', 'Punnapra North', 'Punnapra South', 'Purakad', 'Purakad', 'Ramankary', 'Thaicattussery', 'Thakazhy', 'Thannermukkom', 'Thazhakkara', 'Thuravoor', 'Veeyapuram', 'Veliyanad']
  },
  'Kottayam': {
    'Municipalities': ['Kottayam', 'Changanassery', 'Ettumanoor', 'Kanjirappally', 'Pala', 'Vaikom'],
    'Panchayats': ['Akalakunnam', 'Arpookara', 'Athirampuzha', 'Ayarkunnam', 'Aymanam', 'Bharananganam', 'Chempu', 'Chirakkadavu', 'Elikulam', 'Erattupetta', 'Erumely', 'Ettumanoor', 'Kadanad', 'Kadaplamattam', 'Kaduthuruthy', 'Kanakkary', 'Kangazha', 'Kanjirappally', 'Karoor', 'Karukachal', 'Kidangoor', 'Kooroppada', 'Koottickal', 'Koruthodu', 'Kozhuvanal', 'Kumarakom', 'Kuravilangad', 'Madappally', 'Manjoor', 'Mannanam', 'Marangattupilly', 'Maravanthuruth', 'Meenachil', 'Meenadom', 'Melukavu', 'Moonnilavu', 'Mulakulam', 'Mundakkayam', 'Mutholy', 'Nedumkunnam', 'Neendoor', 'Pala', 'Pallickathodu', 'Pampady', 'Panachikkad', 'Parathodu', 'Paippadu', 'Poonjar', 'Puthuppally', 'Ramapuram', 'Teekoy', 'Thalayazham', 'Thalayolaparambu', 'Thidanadu', 'Thiruvarppu', 'TV Puram', 'Udayanapuram', 'Uzhavoor', 'Vaikom', 'Vakathanam', 'Vazhappally', 'Vazhoor', 'Vechoor', 'Veliyannoor', 'Vellavoor', 'Vijayapuram']
  },
  'Idukki': {
    'Municipalities': ['Thodupuzha', 'Kattappana'],
    'Panchayats': ['Adimaly', 'Alackode', 'Arakkulam', 'Azhutha', 'Bisonvalley', 'Chakkupallam', 'Edavetty', 'Elappara', 'Erattayar', 'Idukki', 'Kanchiyar', 'Karunapuram', 'Kattappana', 'Kodikulam', 'Kumily', 'Konnathady', 'Kudayathoor', 'Manakkadu', 'Mankulam', 'Mariyapuram', 'Munnar', 'Murikkassery', 'Nedumkandam', 'Pallivasal', 'Pampadumpara', 'Peerumedu', 'Purapuzha', 'Rajakumary', 'Rajakkad', 'Santhanpara', 'Senapathy', 'Thodupuzha', 'Udumbannoor', 'Upputhara', 'Vagamon', 'Vandiperiyar', 'Vandanmedu', 'Vazhathope', 'Vathikudy']
  },
  'Ernakulam': {
    'Municipalities': ['Kochi Corporation', 'Aluva', 'Angamaly', 'Kalamassery', 'Kothamangalam', 'Muvattupuzha', 'Perumbavoor', 'Thrikkakara', 'Tripunithura'],
    'Panchayats': ['Aikaranad', 'Alangad', 'Amballoor', 'Asamannoor', 'Avoly', 'Ayavana', 'Chellanam', 'Cheranalloor', 'Chittattukara', 'Choornikkara', 'Chottanikkara', 'Edakkattuvayal', 'Edathala', 'Edavanakkad', 'Elanji', 'Eloor', 'Ezhikkara', 'Kadungalloor', 'Kalady', 'Kalamassery', 'Karukutty', 'Karumalloor', 'Keezhmad', 'Kochi', 'Koothattukulam', 'Koovappady', 'Kottuvally', 'Kumbalam', 'Kumbalangy', 'Kunnathunad', 'Kuzhippilly', 'Malayattoor', 'Manjalloor', 'Maneed', 'Maradu', 'Mazhuvannoor', 'Mudakkuzha', 'Mulavukad', 'Mulanthuruthy', 'Nayarambalam', 'Nedumbassery', 'Njarakkal', 'Okkal', 'Paingottoor', 'Pallarimangalam', 'Pallipuram', 'Paravoor', 'Parakkadave', 'Pindimana', 'Piravam', 'Pootrikka', 'Pothanikkad', 'Puthencruz', 'Puthenvelikkara', 'Ramamangalam', 'Rayamangalam', 'Sreemoolanagaram', 'Thirumarady', 'Thiruvaniyoor', 'Thrikkakkara', 'Thuravoor', 'Vadakkekkara', 'Vadavucode', 'Varapuzha', 'Vazhakulam', 'Velloorkunnam']
  },
  'Thrissur': {
    'Municipalities': ['Thrissur Corporation', 'Chalakudy', 'Chavakkad', 'Guruvayur', 'Irinjalakuda', 'Kodungallur', 'Kunnamkulam', 'Mala', 'Wadakkanchery'],
    'Panchayats': ['Adat', 'Aloor', 'Annamanada', 'Anthikkad', 'Arimpur', 'Athirappilly', 'Avanoor', 'Avinissery', 'Chalakudy', 'Chazhur', 'Chelakkara', 'Cheppara', 'Cherpu', 'Choondal', 'Desamangalam', 'Edakkulam', 'Edavilangu', 'Elavally', 'Engandiyur', 'Eriyad', 'Erumapetty', 'Kadangode', 'Kadavallur', 'Kadukutty', 'Kaipamangalam', 'Kandanassery', 'Karalam', 'Karupadanna', 'Kattur', 'Kodakara', 'Kodannur', 'Koratty', 'Kottapadi', 'Kuzhur', 'Madakkathara', 'Manalur', 'Mathilakam', 'Mattathur', 'Melur', 'Mullassery', 'Mullurkkara', 'Muriyad', 'Nadathara', 'Nenmanikkara', 'Nattika', 'Orumanayur', 'Padiyur', 'Pananchery', 'Paralam', 'Parappukkara', 'Pavaratty', 'Pazhayanur', 'Poomangalam', 'Porathissery', 'Poyya', 'Pudukad', 'Puzhakkal', 'Sreenarayanapuram', 'Talikulam', 'Thalikulam', 'Thekkumkara', 'Thessa', 'Thiruvallur', 'Thiruvilwamala', 'Thrissur', 'Vadakkekad', 'Vadanappally', 'Valappad', 'Varandarappilly', 'Velookkara', 'Venkitangu', 'Wadakkanchery']
  },
  'Palakkad': {
    'Municipalities': ['Palakkad', 'Chittur-Thathamangalam', 'Mannarkkad', 'Ottappalam', 'Pattambi', 'Shoranur'],
    'Panchayats': ['Agali', 'Akathethara', 'Alathur', 'Anakkara', 'Ananganadi', 'Chalissery', 'Elavanchery', 'Elappully', 'Erimayur', 'Eruthenpathy', 'Kadambazhipuram', 'Kannadi', 'Kannambra', 'Kappur', 'Karimpuzha', 'Kavassery', 'Keralassery', 'Kodumba', 'Koduvayur', 'Koottanad', 'Koppam', 'Kozhinjampara', 'Kulukkallur', 'Kumaramputhur', 'Kuzhalmannam', 'Lakkidi Perur', 'Malampuzha', 'Mathur', 'Melarcode', 'Muthalamada', 'Nallepilly', 'Nalleppilly', 'Nemmara', 'Nenmeni', 'Palakkad', 'Pallassana', 'Parali', 'Pattanchery', 'Peringottukurissi', 'Peruvemba', 'Pirayiri', 'Polpully', 'Pudunagaram', 'Pudussery Central', 'Pudussery East', 'Pudussery West', 'Sholayur', 'Sreekrishnapuram', 'Tarur', 'Thachampara', 'Thenkara', 'Thirumittakode', 'Thrithala', 'Vadakarapathy', 'Vadakkenchery', 'Vadavannur', 'Vallapuzha', 'Vaniyamkulam', 'Vilayur']
  },
  'Malappuram': {
    'Municipalities': ['Malappuram', 'Manjeri', 'Nilambur', 'Ponnani', 'Tirur', 'Tanur', 'Kottakkal', 'Perinthalmanna', 'Parappanangadi', 'Valanchery'],
    'Panchayats': ['Abdurahiman Nagar', 'Anakkayam', 'Areacode', 'Athavanad', 'Chelembra', 'Cheekkode', 'Cheriyamundam', 'Cherukara', 'Chokkad', 'Edakkara', 'Edappal', 'Edarikkode', 'Irimbiliyam', 'Kalikavu', 'Kaladi', 'Karuvarakundu', 'Keezhattur', 'Kizhisseri', 'Kondotty', 'Kuruva', 'Kuttippuram', 'Makkaraparamba', 'Mampad', 'Mangalam', 'Mankada', 'Marakkara', 'Melattur', 'Moorkkanad', 'Moothedam', 'Munniyur', 'Nannambra', 'Narukara', 'Niramaruthur', 'Oorakam', 'Othukkungal', 'Pandikkad', 'Perumanna Klari', 'Perumpadappu', 'Ponmala', 'Porur', 'Pulamanthol', 'Pulikkal', 'Purathur', 'Tanalur', 'Tanur', 'Thavanur', 'Thennala', 'Thirunavaya', 'Thirurangadi', 'Trikkalangode', 'Triprangode', 'Urangattiri', 'Valavannur', 'Vazhakkad', 'Vazhayur', 'Vettom', 'Wandoor']
  },
  'Kozhikode': {
    'Municipalities': ['Kozhikode Corporation', 'Feroke', 'Koyilandy', 'Ramanattukara', 'Vadakara'],
    'Panchayats': ['Atholi', 'Ayanchery', 'Balussery', 'Changaroth', 'Chelannur', 'Chemancheri', 'Chengottukavu', 'Cheruvannur', 'Eramala', 'Kakkodi', 'Kakkur', 'Karassery', 'Kayanna', 'Keezhariyur', 'Kizhakkoth', 'Kodanchery', 'Kodiyathur', 'Koduvally', 'Koothali', 'Kunnamangalam', 'Kunnummal', 'Kuruvattoor', 'Kuttiadi', 'Maniyur', 'Maruthonkara', 'Mavoor', 'Melady', 'Memunda', 'Meppayur', 'Nadapuram', 'Naduvannur', 'Narikkuni', 'Nochad', 'Omassery', 'Onchiyam', 'Payyoli', 'Perambra', 'Perumanna', 'Purameri', 'Thikkodi', 'Thiruvallur', 'Thuneri', 'Thurayur', 'Thodannur', 'Ulliyeri', 'Unnikulam', 'Valayam', 'Villiappally']
  },
  'Wayanad': {
    'Municipalities': ['Kalpetta', 'Mananthavady', 'Sulthan Bathery'],
    'Panchayats': ['Edavaka', 'Kalpetta', 'Kaniyambetta', 'Mananthavady', 'Meenangadi', 'Meppadi', 'Mullankolly', 'Muttil', 'Nenmeni', 'Noolpuzha', 'Panamaram', 'Poothadi', 'Pulpally', 'Sulthan Bathery', 'Thariyode', 'Thavinjal', 'Thirunelly', 'Thondernad', 'Vellamunda', 'Vythiri']
  },
  'Kannur': {
    'Municipalities': ['Kannur', 'Thalassery', 'Mattannur', 'Payyannur', 'Taliparamba'],
    'Panchayats': ['Alakode', 'Ancharakandy', 'Aralam', 'Ayyankunnu', 'Azhikode', 'Chapparapadavu', 'Chekkiad', 'Cheruthazham', 'Chirakkal', 'Chittariparamba', 'Chokli', 'Dharmadam', 'Edakkad', 'Eramam Kuttoor', 'Eranholi', 'Iritty', 'Kadannappalli Panniyannur', 'Kadavathur', 'Kalliasseri', 'Kannapuram', 'Kannur', 'Kannadiparamba', 'Karivellur Peralam', 'Karthikapuram', 'Kelakam', 'Koodali', 'Korom', 'Kottayam Malabar', 'Kottiyoor', 'Kunhimangalam', 'Kurumathoor', 'Madayi', 'Malappattam', 'Mangattidam', 'Mayyil', 'Munderi', 'Muzhappilangad', 'Narath', 'Padiyoor', 'Panoor', 'Panniyannur', 'Pariyaram', 'Pathiriyad', 'Pattuvam', 'Payam', 'Payyavoor', 'Peralasseri', 'Peringome Vayakkara', 'Pinarayi', 'Puzhathi', 'Sreekandapuram', 'Thillenkeri', 'Thripangottur', 'Ulikkal', 'Varam']
  },
  'Kasaragod': {
    'Municipalities': ['Kasaragod', 'Kanhangad', 'Nileshwar'],
    'Panchayats': ['Ajanur', 'Badiadka', 'Balal', 'Bedadka', 'Bellur', 'Chemnad', 'Cheruvathur', 'Chittarikkal', 'Delampady', 'Enmakaje', 'Karadka', 'Kumbala', 'Kumbdaje', 'Madhur', 'Manjeshwar', 'Manjeswaram', 'Mogral Puthur', 'Muliyar', 'Paivalike', 'Pallikkara', 'Panathady', 'Pilicode', 'Pullur Periya', 'Puthige', 'Thrikkaripur', 'Udma', 'Vorkady']
  }
};

const EnhancedRequestForm = ({ onClose, onSubmit }) => {
  const { notifications, removeNotification, showSuccess, showError, showInfo } = useNotifications();
  
  // Form state
  const [formData, setFormData] = useState({
    // Basic information
    plot_size: '',
    building_size: '',
    budget_range: '',
    location: '',
    district: '',
    panchayat_municipality: '',
    timeline: '',
    requirements: '',
    
    // Integration features
    requires_house_plan: true,
    enable_progress_tracking: true,
    enable_geo_photos: true,
    
    // House plan requirements
    plot_shape: 'rectangular',
    topography: 'flat',
    num_floors: 1,
    preferred_style: 'modern',
    vastu_compliance: false,
    parking_requirements: 'one_car',
    
    // Room requirements
    bedrooms: 2,
    bathrooms: 2,
    kitchen_type: 'closed',
    living_areas: ['living_room', 'dining_room'],
    special_rooms: [],
    
    // Selected architects
    selected_architect_ids: []
  });
  
  const [architects, setArchitects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  const [showFeatureInfo, setShowFeatureInfo] = useState(false);
  
  // Load architects on component mount
  useEffect(() => {
    fetchArchitects();
  }, []);
  
  const fetchArchitects = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_architects.php');
      const data = await response.json();
      if (data.success) {
        setArchitects(data.architects || []);
      }
    } catch (error) {
      showError('Error', 'Failed to load architects');
    }
  };
  
  const handleInputChange = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };
  
  const handleArrayToggle = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: prev[field].includes(value)
        ? prev[field].filter(item => item !== value)
        : [...prev[field], value]
    }));
  };
  
  const handleArchitectToggle = (architectId) => {
    setFormData(prev => ({
      ...prev,
      selected_architect_ids: prev.selected_architect_ids.includes(architectId)
        ? prev.selected_architect_ids.filter(id => id !== architectId)
        : [...prev.selected_architect_ids, architectId]
    }));
  };
  
  const validateStep = (step) => {
    switch (step) {
      case 1:
        return formData.plot_size && formData.budget_range && formData.location;
      case 2:
        return formData.bedrooms && formData.bathrooms;
      case 3:
        return true; // Feature selection is optional
      case 4:
        return formData.selected_architect_ids.length > 0;
      default:
        return true;
    }
  };
  
  const nextStep = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => Math.min(prev + 1, 4));
    } else {
      showError('Validation Error', 'Please fill in all required fields');
    }
  };
  
  const prevStep = () => {
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateStep(4)) {
      showError('Validation Error', 'Please select at least one architect');
      return;
    }
    
    setLoading(true);
    
    try {
      // Prepare enhanced request data
      const requestData = {
        ...formData,
        special_requirements: [
          ...(formData.vastu_compliance ? ['Vastu compliant design'] : []),
          ...(formData.parking_requirements !== 'none' ? [`${formData.parking_requirements.replace('_', ' ')} parking`] : []),
          ...formData.special_rooms.map(room => `${room.replace('_', ' ')} required`)
        ],
        house_plan_requirements: {
          plot_shape: formData.plot_shape,
          topography: formData.topography,
          rooms: {
            bedrooms: formData.bedrooms,
            bathrooms: formData.bathrooms,
            kitchen_type: formData.kitchen_type,
            living_areas: formData.living_areas,
            special_rooms: formData.special_rooms
          },
          floors: formData.num_floors,
          style_preference: formData.preferred_style,
          vastu_compliance: formData.vastu_compliance,
          parking_requirements: formData.parking_requirements
        }
      };
      
      const response = await fetch('/buildhub/backend/api/homeowner/submit_enhanced_request.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
      });
      
      const result = await response.json();
      
      if (result.success) {
        showSuccess('Success!', 'Your enhanced construction request has been submitted successfully!');
        
        // Show feature integration info
        showInfo('Features Enabled', 
          `Your project includes: ${Object.entries(result.features_enabled)
            .filter(([key, enabled]) => enabled)
            .map(([key]) => key.replace('_', ' '))
            .join(', ')}`
        );
        
        // Call parent callback
        if (onSubmit) {
          onSubmit(result);
        }
        
        // Close form after delay
        setTimeout(() => {
          if (onClose) onClose();
        }, 3000);
        
      } else {
        showError('Submission Failed', result.message || 'Failed to submit request');
      }
      
    } catch (error) {
      showError('Error', 'Network error occurred while submitting request');
    } finally {
      setLoading(false);
    }
  };
  
  const renderStep1 = () => (
    <div className="form-step">
      <h3>📋 Basic Project Information</h3>
      <p>Tell us about your construction project requirements</p>
      
      <div className="form-grid">
        <div className="form-group">
          <label>Plot Size *</label>
          <input
            type="text"
            placeholder="e.g., 30x40 feet or 1200 sqft"
            value={formData.plot_size}
            onChange={(e) => handleInputChange('plot_size', e.target.value)}
            required
          />
        </div>
        
        <div className="form-group">
          <label>Building Size</label>
          <input
            type="text"
            placeholder="e.g., 1500 sqft"
            value={formData.building_size}
            onChange={(e) => handleInputChange('building_size', e.target.value)}
          />
        </div>
        
        <div className="form-group">
          <label>Budget Range *</label>
          <select
            value={formData.budget_range}
            onChange={(e) => handleInputChange('budget_range', e.target.value)}
            required
          >
            <option value="">Select Budget Range</option>
            <option value="5-10 Lakhs">₹5-10 Lakhs</option>
            <option value="10-20 Lakhs">₹10-20 Lakhs</option>
            <option value="20-30 Lakhs">₹20-30 Lakhs</option>
            <option value="30-50 Lakhs">₹30-50 Lakhs</option>
            <option value="50+ Lakhs">₹50+ Lakhs</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>State *</label>
          <select
            value={formData.location}
            onChange={(e) => {
              handleInputChange('location', e.target.value);
              handleInputChange('district', ''); // Reset district when state changes
            }}
            required
          >
            <option value="">Select State</option>
            <optgroup label="South India">
              <option value="Andhra Pradesh">Andhra Pradesh</option>
              <option value="Karnataka">Karnataka</option>
              <option value="Kerala">Kerala</option>
              <option value="Tamil Nadu">Tamil Nadu</option>
              <option value="Telangana">Telangana</option>
              <option value="Puducherry">Puducherry</option>
              <option value="Lakshadweep">Lakshadweep</option>
              <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
            </optgroup>
            <optgroup label="North India">
              <option value="Delhi">Delhi</option>
              <option value="Haryana">Haryana</option>
              <option value="Himachal Pradesh">Himachal Pradesh</option>
              <option value="Jammu and Kashmir">Jammu and Kashmir</option>
              <option value="Ladakh">Ladakh</option>
              <option value="Punjab">Punjab</option>
              <option value="Rajasthan">Rajasthan</option>
              <option value="Uttar Pradesh">Uttar Pradesh</option>
              <option value="Uttarakhand">Uttarakhand</option>
              <option value="Chandigarh">Chandigarh</option>
            </optgroup>
            <optgroup label="East India">
              <option value="Bihar">Bihar</option>
              <option value="Jharkhand">Jharkhand</option>
              <option value="Odisha">Odisha</option>
              <option value="West Bengal">West Bengal</option>
            </optgroup>
            <optgroup label="West India">
              <option value="Goa">Goa</option>
              <option value="Gujarat">Gujarat</option>
              <option value="Maharashtra">Maharashtra</option>
              <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
            </optgroup>
            <optgroup label="Central India">
              <option value="Chhattisgarh">Chhattisgarh</option>
              <option value="Madhya Pradesh">Madhya Pradesh</option>
            </optgroup>
            <optgroup label="Northeast India">
              <option value="Arunachal Pradesh">Arunachal Pradesh</option>
              <option value="Assam">Assam</option>
              <option value="Manipur">Manipur</option>
              <option value="Meghalaya">Meghalaya</option>
              <option value="Mizoram">Mizoram</option>
              <option value="Nagaland">Nagaland</option>
              <option value="Sikkim">Sikkim</option>
              <option value="Tripura">Tripura</option>
            </optgroup>
          </select>
        </div>
        
        {formData.location && stateDistricts[formData.location] && (
          <div className="form-group">
            <label>District *</label>
            <select
              value={formData.district}
              onChange={(e) => {
                handleInputChange('district', e.target.value);
                handleInputChange('panchayat_municipality', ''); // Reset panchayat when district changes
              }}
              required
            >
              <option value="">Select District</option>
              {stateDistricts[formData.location].map(district => (
                <option key={district} value={district}>{district}</option>
              ))}
            </select>
          </div>
        )}
        
        {formData.location === 'Kerala' && formData.district && keralaPanchayatsMunicipalities[formData.district] && (
          <div className="form-group">
            <label>Panchayat / Municipality *</label>
            <select
              value={formData.panchayat_municipality}
              onChange={(e) => handleInputChange('panchayat_municipality', e.target.value)}
              required
            >
              <option value="">Select Panchayat / Municipality</option>
              {keralaPanchayatsMunicipalities[formData.district].Municipalities && (
                <optgroup label="Municipalities">
                  {keralaPanchayatsMunicipalities[formData.district].Municipalities.map(place => (
                    <option key={place} value={place}>{place}</option>
                  ))}
                </optgroup>
              )}
              {keralaPanchayatsMunicipalities[formData.district].Panchayats && (
                <optgroup label="Panchayats">
                  {keralaPanchayatsMunicipalities[formData.district].Panchayats.map(place => (
                    <option key={place} value={place}>{place}</option>
                  ))}
                </optgroup>
              )}
            </select>
          </div>
        )}
        
        <div className="form-group">
          <label>Timeline</label>
          <select
            value={formData.timeline}
            onChange={(e) => handleInputChange('timeline', e.target.value)}
          >
            <option value="">Select Timeline</option>
            <option value="3-6 months">3-6 months</option>
            <option value="6-12 months">6-12 months</option>
            <option value="1-2 years">1-2 years</option>
            <option value="Flexible">Flexible</option>
          </select>
        </div>
        
        <div className="form-group full-width">
          <label>Additional Requirements</label>
          <textarea
            placeholder="Any specific requirements or preferences..."
            value={formData.requirements}
            onChange={(e) => handleInputChange('requirements', e.target.value)}
            rows="3"
          />
        </div>
      </div>
    </div>
  );
  
  const renderStep2 = () => (
    <div className="form-step">
      <h3>🏠 House Design Requirements</h3>
      <p>Specify your house layout and room requirements</p>
      
      <div className="form-grid">
        <div className="form-group">
          <label>Plot Shape</label>
          <select
            value={formData.plot_shape}
            onChange={(e) => handleInputChange('plot_shape', e.target.value)}
          >
            <option value="rectangular">Rectangular</option>
            <option value="square">Square</option>
            <option value="l_shaped">L-Shaped</option>
            <option value="irregular">Irregular</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Topography</label>
          <select
            value={formData.topography}
            onChange={(e) => handleInputChange('topography', e.target.value)}
          >
            <option value="flat">Flat Land</option>
            <option value="sloping">Sloping Land</option>
            <option value="hilly">Hilly Terrain</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Number of Floors</label>
          <select
            value={formData.num_floors}
            onChange={(e) => handleInputChange('num_floors', parseInt(e.target.value))}
          >
            <option value={1}>1 Floor (Ground)</option>
            <option value={2}>2 Floors (Ground + 1st)</option>
            <option value={3}>3 Floors (Ground + 1st + 2nd)</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Preferred Style</label>
          <select
            value={formData.preferred_style}
            onChange={(e) => handleInputChange('preferred_style', e.target.value)}
          >
            <option value="modern">Modern</option>
            <option value="traditional">Traditional</option>
            <option value="contemporary">Contemporary</option>
            <option value="kerala">Kerala Style</option>
            <option value="minimalist">Minimalist</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Bedrooms *</label>
          <select
            value={formData.bedrooms}
            onChange={(e) => handleInputChange('bedrooms', parseInt(e.target.value))}
            required
          >
            <option value="">Select</option>
            <option value={1}>1 Bedroom</option>
            <option value={2}>2 Bedrooms</option>
            <option value={3}>3 Bedrooms</option>
            <option value={4}>4 Bedrooms</option>
            <option value={5}>5+ Bedrooms</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Bathrooms *</label>
          <select
            value={formData.bathrooms}
            onChange={(e) => handleInputChange('bathrooms', parseInt(e.target.value))}
            required
          >
            <option value="">Select</option>
            <option value={1}>1 Bathroom</option>
            <option value={2}>2 Bathrooms</option>
            <option value={3}>3 Bathrooms</option>
            <option value={4}>4+ Bathrooms</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Kitchen Type</label>
          <select
            value={formData.kitchen_type}
            onChange={(e) => handleInputChange('kitchen_type', e.target.value)}
          >
            <option value="closed">Closed Kitchen</option>
            <option value="open">Open Kitchen</option>
            <option value="semi_open">Semi-Open Kitchen</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Parking Requirements</label>
          <select
            value={formData.parking_requirements}
            onChange={(e) => handleInputChange('parking_requirements', e.target.value)}
          >
            <option value="none">No Parking</option>
            <option value="one_car">1 Car Parking</option>
            <option value="two_car">2 Car Parking</option>
            <option value="covered_garage">Covered Garage</option>
          </select>
        </div>
      </div>
      
      <div className="checkbox-groups">
        <div className="checkbox-group">
          <label>Living Areas</label>
          <div className="checkbox-grid">
            {[
              { value: 'living_room', label: '🛋️ Living Room' },
              { value: 'dining_room', label: '🍽️ Dining Room' },
              { value: 'family_room', label: '👨‍👩‍👧‍👦 Family Room' },
              { value: 'drawing_room', label: '🪑 Drawing Room' }
            ].map(area => (
              <label key={area.value} className="checkbox-item">
                <input
                  type="checkbox"
                  checked={formData.living_areas.includes(area.value)}
                  onChange={() => handleArrayToggle('living_areas', area.value)}
                />
                {area.label}
              </label>
            ))}
          </div>
        </div>
        
        <div className="checkbox-group">
          <label>Special Rooms</label>
          <div className="checkbox-grid">
            {[
              { value: 'study_room', label: '📚 Study Room' },
              { value: 'pooja_room', label: '🙏 Pooja Room' },
              { value: 'guest_room', label: '🛏️ Guest Room' },
              { value: 'store_room', label: '📦 Store Room' },
              { value: 'utility_room', label: '🏠 Utility Room' },
              { value: 'home_theater', label: '🎬 Home Theater' }
            ].map(room => (
              <label key={room.value} className="checkbox-item">
                <input
                  type="checkbox"
                  checked={formData.special_rooms.includes(room.value)}
                  onChange={() => handleArrayToggle('special_rooms', room.value)}
                />
                {room.label}
              </label>
            ))}
          </div>
        </div>
      </div>
      
      <div className="form-group">
        <label className="checkbox-item">
          <input
            type="checkbox"
            checked={formData.vastu_compliance}
            onChange={(e) => handleInputChange('vastu_compliance', e.target.checked)}
          />
          🧭 Vastu Compliant Design Required
        </label>
      </div>
    </div>
  );
  
  const renderStep3 = () => (
    <div className="form-step">
      <h3>⚡ Enhanced Features</h3>
      <p>Enable advanced features for your construction project</p>
      
      <div className="feature-cards">
        <div className={`feature-card ${formData.requires_house_plan ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">🏠</span>
            <h4>House Plan Designer</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.requires_house_plan}
                onChange={(e) => handleInputChange('requires_house_plan', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>Interactive drag-and-drop house plan creation with 14 room templates, real-time measurements, and professional visualization.</p>
          <ul>
            <li>✅ Custom floor plan design</li>
            <li>✅ Room templates and layouts</li>
            <li>✅ Real-time area calculations</li>
            <li>✅ Professional architectural output</li>
          </ul>
        </div>
        
        <div className={`feature-card ${formData.enable_geo_photos ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">📍</span>
            <h4>Geo-Tagged Photos</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.enable_geo_photos}
                onChange={(e) => handleInputChange('enable_geo_photos', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>GPS-enabled construction documentation with automatic location tagging and coordinate display on photos.</p>
          <ul>
            <li>✅ Automatic GPS coordinate tagging</li>
            <li>✅ Visual location overlay on photos</li>
            <li>✅ Secure photo sharing</li>
            <li>✅ Construction progress documentation</li>
          </ul>
        </div>
        
        <div className={`feature-card ${formData.enable_progress_tracking ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">📊</span>
            <h4>Progress Reports</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.enable_progress_tracking}
                onChange={(e) => handleInputChange('enable_progress_tracking', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>Comprehensive construction tracking with photo-rich reports, milestone management, and real-time updates.</p>
          <ul>
            <li>✅ Detailed progress documentation</li>
            <li>✅ Milestone tracking and alerts</li>
            <li>✅ Photo-verified work completion</li>
            <li>✅ Timeline and budget monitoring</li>
          </ul>
        </div>
      </div>
      
      <div className="feature-info">
        <button 
          type="button" 
          className="info-button"
          onClick={() => setShowFeatureInfo(!showFeatureInfo)}
        >
          ℹ️ Learn More About Integration
        </button>
        
        {showFeatureInfo && (
          <div className="info-panel">
            <h4>🔗 How Features Work Together</h4>
            <div className="integration-flow">
              <div className="flow-step">
                <span className="step-number">1</span>
                <div className="step-content">
                  <h5>Request Submission</h5>
                  <p>Your requirements are sent to selected architects with feature integration instructions</p>
                </div>
              </div>
              <div className="flow-arrow">→</div>
              <div className="flow-step">
                <span className="step-number">2</span>
                <div className="step-content">
                  <h5>House Plan Creation</h5>
                  <p>Architects use the interactive designer to create custom floor plans based on your requirements</p>
                </div>
              </div>
              <div className="flow-arrow">→</div>
              <div className="flow-step">
                <span className="step-number">3</span>
                <div className="step-content">
                  <h5>Construction Tracking</h5>
                  <p>Contractors document progress with geo-tagged photos and detailed reports</p>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
  
  const renderStep4 = () => (
    <div className="form-step">
      <h3>👨‍💼 Select Architects</h3>
      <p>Choose architects to receive your integrated construction request</p>
      
      <div className="architect-selection">
        {architects.length === 0 ? (
          <div className="no-architects">
            <p>Loading architects...</p>
          </div>
        ) : (
          <div className="architect-grid">
            {architects.map(architect => (
              <div 
                key={architect.id} 
                className={`architect-card ${formData.selected_architect_ids.includes(architect.id) ? 'selected' : ''}`}
                onClick={() => handleArchitectToggle(architect.id)}
              >
                <div className="architect-header">
                  <div className="architect-avatar">
                    {architect.name ? architect.name.charAt(0).toUpperCase() : '👨‍💼'}
                  </div>
                  <div className="architect-info">
                    <h4>{architect.name || 'Architect'}</h4>
                    <p>{architect.specialization || 'Residential Architecture'}</p>
                  </div>
                  <div className="selection-indicator">
                    {formData.selected_architect_ids.includes(architect.id) ? '✅' : '⭕'}
                  </div>
                </div>
                <div className="architect-details">
                  <div className="detail-item">
                    <span>📍 Location:</span>
                    <span>{architect.location || 'Not specified'}</span>
                  </div>
                  <div className="detail-item">
                    <span>⭐ Rating:</span>
                    <span>{architect.rating || 'New'}</span>
                  </div>
                  <div className="detail-item">
                    <span>🏗️ Projects:</span>
                    <span>{architect.project_count || 0}</span>
                  </div>
                </div>
                <div className="architect-features">
                  <span className="feature-badge">🏠 House Plans</span>
                  <span className="feature-badge">📍 Geo Photos</span>
                  <span className="feature-badge">📊 Progress Reports</span>
                </div>
              </div>
            ))}
          </div>
        )}
        
        <div className="selection-summary">
          <p>
            Selected: <strong>{formData.selected_architect_ids.length}</strong> architect(s)
            {formData.selected_architect_ids.length === 0 && (
              <span className="error-text"> - Please select at least one architect</span>
            )}
          </p>
        </div>
      </div>
    </div>
  );
  
  return (
    <div className="enhanced-request-form-overlay">
      <div className="enhanced-request-form">
        <div className="form-header">
          <h2>🚀 Create Enhanced Construction Request</h2>
          <button className="close-button" onClick={onClose}>×</button>
        </div>
        
        <div className="form-progress">
          <div className="progress-steps">
            {[1, 2, 3, 4].map(step => (
              <div 
                key={step} 
                className={`progress-step ${currentStep >= step ? 'active' : ''} ${currentStep === step ? 'current' : ''}`}
              >
                <span className="step-number">{step}</span>
                <span className="step-label">
                  {step === 1 && 'Basic Info'}
                  {step === 2 && 'House Design'}
                  {step === 3 && 'Features'}
                  {step === 4 && 'Architects'}
                </span>
              </div>
            ))}
          </div>
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${(currentStep / 4) * 100}%` }}
            />
          </div>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="form-content">
            {currentStep === 1 && renderStep1()}
            {currentStep === 2 && renderStep2()}
            {currentStep === 3 && renderStep3()}
            {currentStep === 4 && renderStep4()}
          </div>
          
          <div className="form-actions">
            {currentStep > 1 && (
              <button type="button" className="btn-secondary" onClick={prevStep}>
                ← Previous
              </button>
            )}
            
            {currentStep < 4 ? (
              <button type="button" className="btn-primary" onClick={nextStep}>
                Next →
              </button>
            ) : (
              <button type="submit" className="btn-primary" disabled={loading}>
                {loading ? '🔄 Submitting...' : '🚀 Submit Enhanced Request'}
              </button>
            )}
          </div>
        </form>
        
        <NotificationToast 
          notifications={notifications}
          onRemove={removeNotification}
        />
      </div>
    </div>
  );
};

export default EnhancedRequestForm;