// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
import { getFirestore, doc, getDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyAR-OhO2L0DNvXVKubhKUSq98ew5a_9B5w",
  authDomain: "docex-11f2b.firebaseapp.com",
  projectId: "docex-11f2b",
  storageBucket: "docex-11f2b.firebasestorage.app",
  messagingSenderId: "60357696400",
  appId: "1:60357696400:web:3e084194a4170cafcaf115",
  measurementId: "G-M95R83X6YN"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Export instances to be used in your login page
export const auth = getAuth(app);
export const db = getFirestore(app);

/**
 * AUTH STATE OBSERVER
 * This listens for login/logout events and redirects users 
 * based on the 'role' stored in their Firestore document.
 */
onAuthStateChanged(auth, async (user) => {
    if (user) {
        // If we are already on a dashboard, don't redirect again (prevents infinite loops)
        const currentPage = window.location.pathname;
        if (currentPage.includes('dashboard_doc.html') || currentPage.includes('upload.html')) {
            return;
        }

        try {
            // Fetch the user's role from Firestore
            const userDoc = await getDoc(doc(db, "users", user.uid));
            
            if (userDoc.exists()) {
                const userData = userDoc.data();
                if (userData.role === 'doctor') {
                    window.location.href = 'dashboard_doc.html';
                } else {
                    window.location.href = 'upload.html';
                }
            }
        } catch (error) {
            console.error("Error fetching user role:", error);
        }
    } else {
        // Optional: If user is logged out and on a protected page, send to login
        const protectedPages = ['dashboard_doc.html', 'upload.html'];
        if (protectedPages.some(page => window.location.pathname.includes(page))) {
            window.location.href = 'index.html'; // Or your login page name
        }
    }
});