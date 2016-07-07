#include "def.h"
extern FILE *fpbtree;
extern int sqCount; /* statistics: # of successf. queries asked */
extern int uqCount; /* # of unsuccessf. queries */

//extern int iscommon(char *word);
extern int check_word(char *word);
extern int strtolow(char *s);
extern int getpostings(POSTINGSPTR pptr);
extern POSTINGSPTR treesearch_Left(PAGENO PageNo, char *key, char* resultkey);


extern int FreePage(struct PageHdr *PagePtr);
extern struct PageHdr *FetchPage(PAGENO Page);

int ComparePrefix_Left(char *Key, char *Word) {
    
    int m = max(strlen(Key), strlen(Word));
    
    int i = 0;
    for (i = 0; i < m; i++) {
        if (i == strlen(Key)) {
            return (1);
        } else if (i == strlen(Word)) {
            return (2);
        } else if (Key[i] < Word[i]) {
            return (1);
        } else if (Key[i] > Word[i]) {
            return (2);
        }
    }
    return (1);
}













/* KeyListTraverser: Pointer to the list of keys */
/* Key: The new possible key */
/* Found: report result */
int FindInsertionPosition_Left(struct KeyRecord *KeyListTraverser, char *Key,
                                int *last, NUMKEYS NumKeys, int Count) {
    int Result;
    int ComparePrefix_Left(char *Key, char *Word);
    
    /* -christos- the next block probably provides for
     insertion in empty list (useful for insertion in root
     for the first time! */
    
    if (NumKeys == 0) {
        *last = TRUE;
        return (Count);
    }
    
    /* Compare the the possible new key with the key stored in B-Tree */
    Result = ComparePrefix_Left(Key, KeyListTraverser->StoredKey);
    
    NumKeys = NumKeys - 1;
    Count = Count + 1;
    
    
    
    if (NumKeys > 0) {
        if (Result == 1)        /* New key < stored key */
            return (Count - 1); /* Location before stored key */
        else                    /* New key > stored key: keep searching */
        {
            KeyListTraverser = KeyListTraverser->Next;
            return (FindInsertionPosition_Left(KeyListTraverser, Key, last, NumKeys,
                                                Count));
        }
    } else /* this is the last key in the list -- search must terminate */
    {
        if (Result == 1)        /* New key < stored key */
            return (Count - 1); /* Location before stored key */
        else{
            *last = TRUE;
            return (Count); /* New key will be the last key */
        }
    }
}



PAGENO FindPageNumOfChild_Left(struct PageHdr *PagePtr,
                                struct KeyRecord *KeyListTraverser, char *Key,
                                NUMKEYS NumKeys, char * lastMoveRight)

/* A pointer to the list of keys */

/* Possible new key */
{
    /* Auxiliary Definitions */
    int Result;
    char *Word; /* Key stored in B-Tree */
    int ComparePrefix_Left(char *Key, char *Word);
    
    /* Compare the possible new key with key stored in B-Tree */
    Word = KeyListTraverser->StoredKey;
    (*(Word + KeyListTraverser->KeyLen)) = '\0';
    Result = ComparePrefix_Left(Key, Word);
    
    NumKeys = NumKeys - 1;
    
    if (NumKeys > 0) {
        if (Result == 2) { /* New key > stored key:  keep searching */
            strcpy(lastMoveRight, KeyListTraverser->StoredKey);
            KeyListTraverser = KeyListTraverser->Next;
            return (
                    FindPageNumOfChild_Left(PagePtr, KeyListTraverser, Key, NumKeys, lastMoveRight));
        } else                                /* New key <= stored key */
            return (KeyListTraverser->PgNum); /* return left child */
    } else /* This is the last key in this page */
    {
        if (Result == 1)    /* New key <= stored key */
            return (KeyListTraverser->PgNum);  /* return left child */
        else{                                   /* New key > stored key */
            strcpy(lastMoveRight, KeyListTraverser->StoredKey);
            return (PagePtr->PtrToFinalRtgPg); /* return rightmost child */
        }
    }
}

POSTINGSPTR searchLeaf_Left(struct PageHdr *PagePtr, char *key, char* resultkey, int *listhead) {
    struct PageHdr *FetchPage(PAGENO Page);
    struct KeyRecord *KeyListTraverser;
    int InsertionPosition; /* Position for insertion */
    int FindInsertionPosition_Left(struct KeyRecord * KeyListTraverser, char *Key,
                                    int *last, NUMKEYS NumKeys, int Count);
    int Count, last, i;
    last = FALSE;
    Count = 0;
    
    /* Find insertion position */
    KeyListTraverser = PagePtr->KeyListPtr;
    InsertionPosition = FindInsertionPosition_Left(KeyListTraverser, key, &last,
                                                    PagePtr->NumKeys, Count);
    
    //printf("%d\n",InsertionPosition);
    
    if (InsertionPosition == 0){// at the first of a list
        /*
        Find the most left leaf
         */
        struct PageHdr *p, *tempp;
        p = FetchPage(ROOT);
        while (IsNonLeaf(p)) { /* follow leftmost ptr */
            tempp = p;
            p = FetchPage((tempp->KeyListPtr)->PgNum);
            FreePage(tempp);
        }/* now "p" should point to the alphabetically first page */
        
        
        if (PagePtr->PgNum == p->PgNum){// first page
            strcpy(resultkey, "*NONE*");
            return(NONEXISTENT);
        }
        else{
            *listhead = 1;
            return(NONEXISTENT);
        }
        
        
        
    }else{
        for (i = 0; i < InsertionPosition - 1; i++)
            KeyListTraverser = KeyListTraverser->Next;
        
        strcpy(resultkey, KeyListTraverser->StoredKey);
        return (KeyListTraverser->Posting);
    }
    
    
    
    

}


/**
 * recursive call to find the page in which the key should reside
 * and return the page number (guaranteed to be a leaf page).
 */
PAGENO treesearch_page_Left(PAGENO PageNo, char *key, char* lastMoveRight) {
    PAGENO result;
    struct PageHdr *PagePtr = FetchPage(PageNo);
    if (IsLeaf(PagePtr)) { /* found leaf */
        result = PageNo;
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys == 0)) {
        /* keys, if any, will be stored in Page# 2
         THESE PIECE OF CODE SHOULD GO soon! **/
 
        result = treesearch_page_Left(FIRSTLEAFPG, key, lastMoveRight);
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys > 0)) {
        PAGENO ChildPage = FindPageNumOfChild_Left(PagePtr, PagePtr->KeyListPtr, key,
                                                    PagePtr->NumKeys, lastMoveRight);

        result = treesearch_page_Left(ChildPage, key, lastMoveRight);
    } else {
        assert(0 && "this should never happen");
    }
    FreePage(PagePtr);
    return result;
}

/**
 * find the posting pointer to which the key should reside, given the
 * starting page number to look at.
 *
 * to search the whole tree, pass in ROOT as the page number.
 */
POSTINGSPTR treesearch_Left(PAGENO PageNo, char *key, char* resultkey) {
    char lastMoveRight[MAXWORDSIZE];
    int listhead = 0;
    //printf("initial lastMoveRight pgn %ld", lastMoveRight->PgNum);
    
    /* recursive call to find page number */
    const PAGENO page = treesearch_page_Left(PageNo, key, lastMoveRight);
    //printf("lastMoveRight stored key %s", lastMoveRight->StoredKey);
    
    /* from page number we traverse the leaf page */
    struct PageHdr *PagePtr = FetchPage(page);
    POSTINGSPTR result = searchLeaf_Left(PagePtr, key, resultkey, &listhead);
    
    /* Deal with key list head */
    
    if (result == NONEXISTENT && listhead == 1){// need to search again according to lastMoveRight
        /*
        printf("need to search again according to lastMoveRight\n");
        struct PageHdr *p = FetchPage(lastMoveRight->PgNum);
        while (IsNonLeaf(p)) {
            
            printf("child pagenum %ld\n", p->PtrToFinalRtgPg);
            
            p = FetchPage(p->PtrToFinalRtgPg);//right most child
        }
        
        struct KeyRecord *k = p->KeyListPtr;
        for (int i = 0; i < (p->NumKeys - 1); i++){
            k = k->Next;
        }
         */
        strcpy(resultkey, lastMoveRight);
        //result = k->Posting;
    }
    
    
    
    FreePage(PagePtr);
    return result;
}








void get_leftbracket(char *key, char *resultkey) {
    POSTINGSPTR pptr;
    
    /* Print an error message if strlen(key) > MAXWORDSIZE */
    if (strlen(key) > MAXWORDSIZE) {
        //printf("ERROR in \"search\":  Length of key Exceeds Maximum Allowed\n");
        strcpy(resultkey,"*NONE*");
        return;
    }
    /*
     if (iscommon(key)) {
     printf("\"%s\" is a common word - no searching is done\n", key);
     return;
     }
     */
    if (check_word(key) == FALSE) {
        strcpy(resultkey,"*NONE*");
        return;
    }
    /* turn to lower case, for uniformity */
    strtolow(key);
    
    pptr = treesearch_Left(ROOT, key, resultkey);
}

