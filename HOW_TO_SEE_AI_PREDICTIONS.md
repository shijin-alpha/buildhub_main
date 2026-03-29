# How to See AI Predictions in Contractor Dashboard

## Current Situation

✅ Database schema fixed
✅ Backend API updated  
✅ Frontend code updated
✅ Frontend rebuilt
❌ Your current inbox item doesn't have AI predictions

## Why You Don't See AI Predictions

The request from Amal Samuel was sent **without a layout_id**. This means:
- It was sent directly as a house plan
- No layout_request record exists
- AI predictions can't be stored in layout_requests table
- This is an older workflow

## ✅ SOLUTION: Test with Contractor 51

Contractor 51 has proper requests with AI predictions already added.

### Steps:

1. **Logout** from current contractor account

2. **Find contractor 51's login credentials** in your users table:
   ```sql
   SELECT id, email, first_name, last_name 
   FROM users 
   WHERE id = 51;
   ```

3. **Login as contractor 51**

4. **Go to Inbox**

5. **You will see AI predictions!** 🎉

## Alternative: Submit New Homeowner Request

For the AI system to work properly with NEW requests:

1. **Login as Homeowner**
2. **Submit Layout Request** (not direct house plan)
3. **AI predictions will be generated automatically**
4. **Send to Contractor**
5. **Login as Contractor**
6. **See AI predictions in inbox**

## What You'll See

```
🤖 AI Risk Assessment
💰 Cost Overrun Risk: 🔴 High (87.5%)
⏰ Time Delay Risk: 🟡 Medium (62.5%)
🎯 Key Risk Factors: [list]
💡 Recommendation: Add 15-20% contingency...
```

This will appear BETWEEN the homeowner message and technical details.

## Summary

Your system is READY. You just need to view a request that has AI predictions.

**Quickest way**: Login as contractor 51
