import { createRouter, createWebHistory } from 'vue-router'
import AdminDashboard from '../views/AdminDashboard.vue'
import RecruiterDashboard from '../views/RecruiterDashboard.vue'
import CandidateDashboard from '../views/CandidateDashboard.vue'
import LoginPage from '../components/auth/LoginPage.vue'
import CandidateApplications from '../views/CandidateApplications.vue'
import CandidateJobs from '../views/CandidateJobs.vue'
import CandidateInterviews from '../views/CandidateInterviews.vue'
import CandidateProfile from '../views/CandidateProfile.vue'
import CandidateCV from '../views/CandidateCV.vue'
import CandidateSavedJobs from '../views/CandidateSavedJobs.vue'
import RecruiterJobs from '../views/RecruiterJobs.vue'
import RecruiterCandidates from '../views/RecruiterCandidates.vue'
import RecruiterSearch from '../views/RecruiterSearch.vue'
import RecruiterAIHelper from '../views/RecruiterAIHelper.vue'
import RecruiterMessages from '../views/RecruiterMessages.vue'
import RecruiterProfile from '../views/RecruiterProfile.vue'
import CandidateAIHelper from '../views/CandidateAIHelper.vue'
import AdminRecruiters from '../views/AdminRecruiters.vue'
import AdminCandidates from '../views/AdminCandidates.vue'
import AdminJobs from '../views/AdminJobs.vue'
import AdminApplications from '../views/AdminApplications.vue'
import AdminAnalytics from '../views/AdminAnalytics.vue'
import AdminSettings from '../views/AdminSettings.vue'
import AdminProfile from '../views/AdminProfile.vue'



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: LoginPage,
    },
    {
      path: '/admin',
      name: 'admin',
      component: AdminDashboard,
      meta: { requiresAuth: true, role: 'admin' }
    },
    {
      path: '/recruiter',
      name: 'recruiter',
      component: RecruiterDashboard,
      meta: { requiresAuth: true, role: 'recruiter' }
    },
    {
      path: '/candidate',
      name: 'candidate',
      component: CandidateDashboard,
      meta: { requiresAuth: true, role: 'candidate' }
    },


    {
  path: '/candidate/applications',
  name: 'candidate-applications',
  component: CandidateApplications,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/candidate/jobs',
  name: 'candidate-jobs',
  component: CandidateJobs,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/candidate/interviews',
  name: 'candidate-interviews',
  component: CandidateInterviews,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/candidate/profile',
  name: 'candidate-profile',
  component: CandidateProfile,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/candidate/cv',
  name: 'candidate-cv',
  component: CandidateCV,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/candidate/saved',
  name: 'candidate-saved',
  component: CandidateSavedJobs,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/recruiter/jobs',
  name: 'recruiter-jobs',
  component: RecruiterJobs,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/recruiter/candidates',
  name: 'recruiter-candidates',
  component: RecruiterCandidates,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/recruiter/search',
  name: 'recruiter-search',
  component: RecruiterSearch,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/recruiter/ai-helper',
  name: 'recruiter-ai-helper',
  component: RecruiterAIHelper,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/recruiter/messages',
  name: 'recruiter-messages',
  component: RecruiterMessages,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/recruiter/profile',
  name: 'recruiter-profile',
  component: RecruiterProfile,
  meta: { requiresAuth: true, role: 'recruiter' }
},
{
  path: '/candidate/ai-helper',
  name: 'candidate-ai-helper',
  component: CandidateAIHelper,
  meta: { requiresAuth: true, role: 'candidate' }
},
{
  path: '/admin/recruiters',
  name: 'admin-recruiters',
  component: AdminRecruiters,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/candidates',
  name: 'admin-candidates',
  component: AdminCandidates,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/jobs',
  name: 'admin-jobs',
  component: AdminJobs,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/applications',
  name: 'admin-applications',
  component: AdminApplications,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/analytics',
  name: 'admin-analytics',
  component: AdminAnalytics,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/settings',
  name: 'admin-settings',
  component: AdminSettings,
  meta: { requiresAuth: true, role: 'admin' }
},
{
  path: '/admin/profile',
  name: 'admin-profile',
  component: AdminProfile,
  meta: { requiresAuth: true, role: 'admin' }
},
  ],
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.meta.requiresAuth && !token) {
    next('/')
  } else if (to.meta.role && user.role !== to.meta.role) {
    // Rediriger selon le rôle
    const roleMap = {
      'admin': '/admin',
      'recruiter': '/recruiter',
      'candidate': '/candidate'
    }
    next(roleMap[user.role] || '/')
  } else {
    next()
  }
})

export default router